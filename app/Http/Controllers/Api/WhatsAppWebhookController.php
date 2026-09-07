<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastCampaign;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
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

        // 🎯 1. TANGKAP PROGRESS BROADCAST
        if (!empty($data['type']) && $data['type'] === 'broadcast.progress') {
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

        $channel = Channel::where('identifier', $sessionId)->first();
        if (!$channel) return response()->json(['message' => 'Channel tidak terdaftar'], 200);

        if ($event === 'session.status' || $event === 'session.connected') {
            $channel->update(['status' => 'connected']);
            return response()->json(['status' => 'connected']);
        }

        // 🎯 2. TANGKAP PESAN MASUK & KELUAR (INBOUND & OUTBOUND BROADCAST)
        if ($event === 'message.received' && !empty($data)) {
            $officeId = $channel->office_id;
            $rawFrom = $data['from'] ?? '';
            $isGroup = !empty($data['isGroup']);
            $senderJid = $data['senderJid'] ?? $rawFrom;
            $senderName = $data['pushName'] ?? $senderJid;
            $groupName = $data['groupName'] ?? null;
            $text = $data['text'] ?? null;
            $messageId = $data['messageId'] ?? null;
            $media = $data['media'] ?? null;
            $isFromMe = !empty($data['fromMe']);

            // Objek Kontak
            if ($isGroup) {
                $targetJid = $rawFrom . '@g.us';
                $cleanGroupName = $groupName ?: ('Grup ' . substr($rawFrom, -5));
                $contact = Contact::firstOrCreate(
                    ['office_id' => $officeId, 'wa_jid' => $targetJid],
                    ['name' => $cleanGroupName, 'pipeline_stage' => 'lead']
                );
            } else {
                $targetJid = $rawFrom;
                $contact = Contact::firstOrCreate(
                    ['office_id' => $officeId, 'wa_jid' => $targetJid],
                    [
                        'name' => $isFromMe ? ($senderJid ?: 'Customer') : $senderName,
                        'phone_number' => str_contains($targetJid, '@lid') ? null : $targetJid,
                        'pipeline_stage' => 'lead',
                    ]
                );
            }

            // Room Percakapan
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

            // Simpan Media jika ada
            $mediaUrl = null;
            $messageType = 'text';

            if ($media && !empty($media['base64'])) {
                $fileBuffer = base64_decode($media['base64']);
                $mime = $media['mimeType'] ?? '';
                $ext = str_contains($mime, 'video') ? 'mp4' : (str_contains($mime, 'pdf') ? 'pdf' : (str_contains($mime, 'image') ? 'jpg' : 'bin'));
                $filename = 'media/' . Str::uuid() . '.' . $ext;
                Storage::disk('public')->put($filename, $fileBuffer);
                $mediaUrl = Storage::url($filename);
                $messageType = $ext === 'mp4' ? 'video' : ($ext === 'pdf' || $ext === 'bin' ? 'document' : 'image');
            }

            $cleanPayload = $data;
            if (isset($cleanPayload['media']['base64'])) unset($cleanPayload['media']['base64']);

            $existingMessage = Message::where('external_message_id', $messageId)->first();
            if ($existingMessage) return response()->json(['status' => 'already exists']);

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

            // Eksekusi AI jika pesan dari customer personal dan bot aktif
            if (!$isFromMe && $conversation->is_bot_active && !$isGroup) {
                try {
                    $gemini = app(\App\Services\GeminiAiService::class);
                    $baileys = app(\App\Services\BaileysService::class);

                    $localImagePath = null;
                    if ($messageType === 'image' && $mediaUrl) {
                        $localImagePath = storage_path('app/public/' . str_replace('/storage/', '', $mediaUrl));
                    }

                    $botReply = $gemini->generateReply($conversation, $localImagePath);
                    if ($botReply) {
                        $botMessage = Message::create([
                            'conversation_id' => $conversation->id,
                            'office_id' => $officeId,
                            'sender_type' => 'bot',
                            'message_type' => 'text',
                            'message_body' => $botReply,
                            'is_read' => true,
                        ]);

                        $conversation->update(['last_message_at' => now()]);
                        $targetNumber = $contact->wa_jid ?: $contact->phone_number;
                        $res = $baileys->sendTextMessage($channel->identifier, $targetNumber, $botReply);

                        if (!empty($res['data']['messageId'])) {
                            $botMessage->update(['external_message_id' => $res['data']['messageId']]);
                        }

                        $gemini->analyzeAndSummarizeLead($conversation);
                    }
                } catch (\Exception $e) {}
            }

            return response()->json(['status' => 'saved']);
        }

        return response()->json(['status' => 'ignored']);
    }
}
