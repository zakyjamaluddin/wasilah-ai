<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastCampaign;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\BaileysService;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $sessionId = $request->input('sessionId');
        $data = $request->input('data');

        if (!$sessionId) return response()->json(['error' => 'sessionId missing'], 400);

        $channel = Channel::where('identifier', $sessionId)->first();
        if (!$channel) return response()->json(['message' => 'Channel tidak terdaftar'], 200);

        if ($event === 'session.status' || $event === 'session.connected') {
            $channel->update(['status' => 'connected']);
            return response()->json(['status' => 'connected']);
        }

        // 3. EVENT: Update Progress Broadcast Campaign Real-time
        if ($event === 'session.status' && !empty($data['type']) && $data['type'] === 'broadcast.progress') {
            $broadcastId = $data['broadcastId'] ?? null;
            $progress = $data['progress'] ?? [];

            if ($broadcastId) {
                $campaign = BroadcastCampaign::where('external_broadcast_id', $broadcastId)->first();
                if ($campaign) {
                    $sent = $progress['sent'] ?? $campaign->sent_count;
                    $failed = $progress['failed'] ?? $campaign->failed_count;
                    $total = $progress['total'] ?? $campaign->total_recipients;

                    $isCompleted = ($sent + $failed) >= $total;

                    $campaign->update([
                        'sent_count' => $sent,
                        'failed_count' => $failed,
                        'status' => $isCompleted ? 'completed' : 'processing', 
                        'completed_at' => $isCompleted ? now() : null,
                    ]);
                }
            }
            return response()->json(['status' => 'broadcast progress updated']);
        }

        if ($event === 'message.received' && !empty($data)) {
            $officeId = $channel->office_id;
            $rawFrom = $data['from'] ?? '';
            $isGroup = !empty($data['isGroup']);
            $senderJid = $data['senderJid'] ?? $rawFrom;
            $senderName = $data['pushName'] ?? $senderJid; // Otomatis nomor HP jika pushName kosong
            $groupName = $data['groupName'] ?? null;
            $text = $data['text'] ?? null;
            $messageId = $data['messageId'] ?? null;
            $media = $data['media'] ?? null;
            $isFromMe = !empty($data['fromMe']);

            // 1. Identifikasi Objek Kontak / Grup
            if ($isGroup) {
                $targetJid = $rawFrom . '@g.us';
                $cleanGroupName = $groupName ?: ('Grup ' . substr($rawFrom, -5));
                $contact = Contact::firstOrCreate(
                    ['office_id' => $officeId, 'wa_jid' => $targetJid],
                    ['name' => $cleanGroupName, 'pipeline_stage' => 'lead']
                );
                if ($groupName && $contact->name !== $groupName) {
                    $contact->update(['name' => $groupName]);
                }
            } else {
                $targetJid = $rawFrom;
                $contact = Contact::firstOrCreate(
                    ['office_id' => $officeId, 'wa_jid' => $targetJid],
                    [
                        'name' => $isFromMe ? 'Customer' : $senderName,
                        'phone_number' => str_contains($targetJid, '@lid') ? null : $targetJid,
                        'pipeline_stage' => 'lead',
                    ]
                );
                if (!$isFromMe && $senderName && $contact->name === 'Customer') {
                    $contact->update(['name' => $senderName]);
                }
            }

            // 2. Room Percakapan
            $conversation = Conversation::firstOrCreate(
                [
                    'office_id' => $officeId,
                    'channel_id' => $channel->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'channel_type' => 'whatsapp',
                    'is_bot_active' => $isGroup ? false : $channel->is_bot_enabled,
                ]
            );

            // 3. Tangani File Media (Gambar, Video, Dokumen)
            $mediaUrl = null;
            $messageType = 'text';

            if ($media && !empty($media['base64'])) {
                $fileBuffer = base64_decode($media['base64']);
                $mime = $media['mimeType'] ?? '';

                if (str_contains($mime, 'video')) {
                    $extension = 'mp4';
                    $messageType = 'video';
                } elseif (str_contains($mime, 'pdf')) {
                    $extension = 'pdf';
                    $messageType = 'document';
                } elseif (str_contains($mime, 'image')) {
                    $extension = 'jpg';
                    $messageType = 'image';
                } else {
                    $extension = 'bin';
                    $messageType = 'document';
                }

                $filename = 'media/' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->put($filename, $fileBuffer);
                $mediaUrl = Storage::url($filename);
            }

            $existingMessage = Message::where('external_message_id', $messageId)->first();
            if ($existingMessage) return response()->json(['status' => 'already exists']);

            $cleanPayload = $data;
            if (isset($cleanPayload['media']['base64'])) {
                unset($cleanPayload['media']['base64']);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'office_id' => $officeId,
                'sender_type' => $isFromMe ? 'agent' : 'customer',
                'message_type' => $messageType,
                'message_body' => ($isGroup && !$isFromMe ? "[{$senderName}]: " : '') . ($text ?? ''),
                'media_url' => $mediaUrl,
                'external_message_id' => $messageId,
                'raw_payload' => $cleanPayload,
                'is_read' => $isFromMe,
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => $isFromMe ? 0 : ($conversation->unread_count + 1),
            ]);

            // =========================================================================
            // 🔥 EKSEKUSI AI CHATBOT OTOMATIS (JIKA BOT AKTIF & BUKAN CHAT DARI HP SENDIRI)
            // =========================================================================
            if (!$isFromMe && $conversation->is_bot_active && !$isGroup) {
                try {
                    $gemini = app(GeminiAiService::class);
                    $baileys = app(BaileysService::class);

                    // Ambil path fisik gambar jika ada
                    $localImagePath = null;
                    if ($messageType === 'image' && $mediaUrl) {
                        $localImagePath = storage_path('app/public/' . str_replace('/storage/', '', $mediaUrl));
                    }

                    // 1. Generate Jawaban dari Gemini
                    $botReply = $gemini->generateReply($conversation, $localImagePath);

                    if ($botReply) {
                        // 2. Simpan Pesan Bot ke Database
                        $botMessage = Message::create([
                            'conversation_id' => $conversation->id,
                            'office_id' => $officeId,
                            'sender_type' => 'bot',
                            'message_type' => 'text',
                            'message_body' => $botReply,
                            'is_read' => true,
                        ]);

                        $conversation->update(['last_message_at' => now()]);

                        // 3. Kirim Jawaban ke WhatsApp Customer via VPS Baileys
                        $targetJid = $contact->wa_jid ?: $contact->phone_number;
                        $response = $baileys->sendTextMessage($channel->identifier, $targetJid, $botReply);

                        if (!empty($response['data']['messageId'])) {
                            $botMessage->update(['external_message_id' => $response['data']['messageId']]);
                        }

                        // 4. Analisis Kebutuhan Leads & Update Pipeline (Cold -> Hot)
                        $gemini->analyzeAndSummarizeLead($conversation);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("[AI Bot Execution Error] " . $e->getMessage());
                }
            }

            return response()->json(['status' => 'saved']);
        }

        return response()->json(['status' => 'ignored']);
    }
}

