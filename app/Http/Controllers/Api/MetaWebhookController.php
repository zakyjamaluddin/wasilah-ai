<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ComposioService;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MetaWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.meta.verify_token', 'wasilah_secret_meta_webhook_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Helper Cerdas: Menemukan Channel Kantor yang Tepat Berdasarkan Page ID
     */
    protected function resolveChannel(string $pageId, string $object): ?Channel
    {
        $platformType = $object === 'instagram' ? 'instagram' : 'facebook';

        // 1. Prioritas Utama: Cari channel yang Page ID-nya cocok persis
        $channel = Channel::with('office.composioAccount')
            ->where('type', $platformType)
            ->where('identifier', $pageId)
            ->first();

        if ($channel) {
            return $channel;
        }

        // 2. Jika belum ada yang cocok, cari Channel di Kantor yang memiliki Akun Composio Aktif
        $channel = Channel::with('office.composioAccount')
            ->where('type', $platformType)
            ->whereHas('office', function ($q) {
                $q->whereNotNull('composio_account_id');
            })
            ->first();

        // Kunci Page ID ini ke channel kantor aktif tersebut
        if ($channel) {
            $channel->update(['identifier' => $pageId]);
            Log::info("🔗 [Auto-Bind Page ID] Page ID {$pageId} berhasil dikaitkan ke Kantor: {$channel->office->name}");
            return $channel;
        }

        return null;
    }

    public function handle(Request $request, ComposioService $composio, GeminiAiService $gemini)
    {
        $body = $request->all();
        Log::info("=== [META INBOUND EVENT] ===");
        Log::info(json_encode($body, JSON_PRETTY_PRINT));

        $object = $body['object'] ?? '';

        if ($object === 'page' || $object === 'instagram') {
            foreach ($body['entry'] as $entry) {
                $pageId = (string) ($entry['id'] ?? '');

                // 🔥 RESOLVE CHANNEL DENGAN AMAN & TEPAT SASARAN
                $channel = $this->resolveChannel($pageId, $object);

                if (!$channel || !$channel->office) {
                    Log::warning("⚠️ Channel tidak ditemukan untuk Page ID: {$pageId}");
                    continue;
                }

                $office = $channel->office;
                $officeId = $office->id;

                // =============================================================
                // A. EVENT: PESAN DM (MESSENGER & INSTAGRAM DM)
                // =============================================================
                if (!empty($entry['messaging'])) {
                    foreach ($entry['messaging'] as $messaging) {
                        $senderId = (string) ($messaging['sender']['id'] ?? '');
                        $text = $messaging['message']['text'] ?? '';
                        $messageId = $messaging['message']['mid'] ?? null;
                        $attachments = $messaging['message']['attachments'] ?? [];

                        if ($senderId === $pageId || ($messaging['message']['is_echo'] ?? false)) continue;

                        $mediaUrl = null;
                        $messageType = 'text';

                        if (!empty($attachments)) {
                            $firstAttachment = $attachments[0];
                            $attachType = $firstAttachment['type'] ?? 'image';
                            $metaCdnUrl = $firstAttachment['payload']['url'] ?? null;

                            if ($metaCdnUrl) {
                                try {
                                    $downloadRes = Http::withOptions([
                                        'allow_redirects' => true,
                                        'verify' => false,
                                        'timeout' => 30,
                                    ])->withHeaders([
                                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                        'Accept' => '*/*',
                                    ])->get($metaCdnUrl);

                                    if ($downloadRes->successful() && strlen($downloadRes->body()) > 100) {
                                        $ext = $attachType === 'video' ? 'mp4' : ($attachType === 'file' ? 'pdf' : 'jpg');
                                        $filename = 'media/' . Str::uuid() . '.' . $ext;

                                        Storage::disk('public')->put($filename, $downloadRes->body());
                                        $mediaUrl = Storage::url($filename);
                                        $messageType = $attachType === 'video' ? 'video' : ($attachType === 'file' ? 'document' : 'image');
                                    }
                                } catch (\Exception $e) {
                                    Log::error("[Meta Media Download Error] " . $e->getMessage());
                                }
                            }
                        }

                        if (empty($text) && $mediaUrl) {
                            $text = $messageType === 'video' ? '🎥 [Video]' : ($messageType === 'document' ? '📄 [Dokumen]' : '🖼️ [Foto]');
                        }

                        if (empty($text) && empty($mediaUrl)) continue;

                        if ($messageId) {
                            $lockKey = "meta_inbound_msg_{$messageId}";
                            if (Cache::has($lockKey)) continue;
                            Cache::put($lockKey, true, now()->addMinutes(10));
                        }

                        // =============================================================
                        // 🔥 KHUSUS INSTAGRAM: DETEKSI REAL PSID & LOG TRANSPARAN
                        // =============================================================
                        $realPsid = $senderId;
                        $replyMid = $messageId;
                        $contactName = ($object === 'instagram' ? 'IG User ' : 'FB User ') . substr($senderId, -4);

                        if ($object === 'instagram') {
                            $igSenderData = $composio->getLatestInstagramSender($office);
                            if ($igSenderData && !empty($igSenderData['psid'])) {
                                $realPsid = $igSenderData['psid'];
                                $replyMid = $igSenderData['mid'] ?? $messageId;
                                $contactName = '@' . ($igSenderData['sender_username'] ?? substr($realPsid, -4));
                            }
                        }

                        $channelType = $object === 'instagram' ? 'ig_dm' : 'fb_dm';

                        // 1. Kontak
                        $contact = Contact::firstOrCreate(
                            ['office_id' => $officeId, 'fb_user_id' => $realPsid],
                            [
                                'name' => $contactName,
                                'ig_username' => $object === 'instagram' ? str_replace('@', '', $contactName) : null,
                                'pipeline_stage' => 'lead'
                            ]
                        );

                        // 2. Percakapan
                        $conversation = Conversation::firstOrCreate(
                            ['office_id' => $officeId, 'channel_id' => $channel->id, 'contact_id' => $contact->id],
                            ['channel_type' => $channelType, 'is_bot_active' => $channel->is_bot_enabled]
                        );

                        // 3. Simpan Pesan Masuk
                        Message::create([
                            'conversation_id'     => $conversation->id,
                            'office_id'           => $officeId,
                            'sender_type'         => 'customer',
                            'message_type'        => $messageType,
                            'message_body'        => $text,
                            'media_url'           => $mediaUrl,
                            'external_message_id' => $messageId,
                            'is_read'             => false,
                        ]);

                        $conversation->update([
                            'last_message_at' => now(),
                            'unread_count'    => $conversation->unread_count + 1
                        ]);

                        // 4. BALAS OTOMATIS OLEH AI GEMINI ➡️ DIKIRIMKAN VIA COMPOSIO
                        if ($conversation->is_bot_active) {
                            $localImagePath = null;
                            if ($messageType === 'image' && $mediaUrl) {
                                $relativePath = str_replace('/storage/', '', $mediaUrl);
                                $localImagePath = storage_path('app/public/' . $relativePath);
                            }

                            $botReply = $gemini->generateReply($conversation, $localImagePath);

                            if ($botReply) {
                                if ($object === 'instagram') {
                                    $sendRes = $composio->sendInstagramDm($office, $realPsid, $botReply, $replyMid);
                                } else {
                                    $sendRes = $composio->sendFacebookMessenger($office, $senderId, $botReply, $pageId);
                                }

                                Log::info("🚀 [Composio DM Send Response via {$office->slug}]:", $sendRes);

                                Message::create([
                                    'conversation_id' => $conversation->id,
                                    'office_id'       => $officeId,
                                    'sender_type'     => 'bot',
                                    'message_type'    => 'text',
                                    'message_body'    => $botReply,
                                    'is_read'         => true,
                                ]);

                                $gemini->analyzeAndSummarizeLead($conversation);
                            }
                        }
                    }
                }

                // =============================================================
                // B. EVENT: FEED & KOMENTAR (FB & IG)
                // =============================================================
                if (!empty($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        $field = $change['field'] ?? '';
                        $val = $change['value'] ?? [];

                        // 🎯 1. FACEBOOK AUTO FIRST COMMENT
                        $postTypes = ['status', 'photo', 'video', 'post', 'share'];
                        if ($object === 'page' && $field === 'feed' && in_array($val['item'] ?? '', $postTypes) && ($val['verb'] ?? '') === 'add') {
                            $postId = $val['post_id'] ?? $val['id'] ?? null;
                            $firstCommentText = $channel->credentials['auto_first_comment'] ?? null;

                            if ($postId && $firstCommentText) {
                                $composio->replyFacebookComment($office, (string)$postId, $firstCommentText);
                            }
                        }

                        // 🎯 2. FACEBOOK AI AUTO-REPLY KOMENTAR
                        if ($object === 'page' && $field === 'feed' && ($val['item'] ?? '') === 'comment' && ($val['verb'] ?? '') === 'add') {
                            $commentId = (string) ($val['comment_id'] ?? '');
                            $senderId = (string) ($val['from']['id'] ?? '');
                            $senderName = $val['from']['name'] ?? 'Netizen FB';
                            $commentText = $val['message'] ?? '';

                            if ($commentId && $senderId !== $pageId && !empty($commentText) && $channel->is_bot_enabled) {
                                $lockKey = "fb_comment_lock_{$commentId}";
                                if (Cache::has($lockKey)) continue;
                                Cache::put($lockKey, true, now()->addMinutes(30));

                                $contact = Contact::firstOrCreate(
                                    ['office_id' => $officeId, 'fb_user_id' => $senderId],
                                    ['name' => $senderName, 'pipeline_stage' => 'lead']
                                );

                                $conversation = Conversation::firstOrCreate(
                                    ['office_id' => $officeId, 'channel_id' => $channel->id, 'contact_id' => $contact->id],
                                    ['channel_type' => 'fb_comment', 'is_bot_active' => true]
                                );

                                Message::create([
                                    'conversation_id'     => $conversation->id,
                                    'office_id'           => $officeId,
                                    'sender_type'         => 'customer',
                                    'message_type'        => $messageType ?? 'text',
                                    'message_body'        => $commentText,
                                    'external_message_id' => $commentId,
                                    'is_read'             => false,
                                ]);

                                $conversation->update([
                                    'last_message_at' => now(),
                                    'unread_count'    => $conversation->unread_count + 1
                                ]);

                                $aiReply = $gemini->generateReply($conversation);
                                if ($aiReply) {
                                    $composio->replyFacebookComment($office, $commentId, $aiReply);

                                    Message::create([
                                        'conversation_id' => $conversation->id,
                                        'office_id'       => $officeId,
                                        'sender_type'     => 'bot',
                                        'message_type'    => 'text',
                                        'message_body'    => $aiReply,
                                        'is_read'         => true,
                                    ]);
                                }
                            }
                        }

                        // 🎯 3. INSTAGRAM AI AUTO-REPLY KOMENTAR
                        if ($object === 'instagram' && $field === 'comments') {
                            $commentId = (string) ($val['id'] ?? '');
                            $senderId = (string) ($val['from']['id'] ?? '');
                            $senderUsername = $val['from']['username'] ?? 'Netizen IG';
                            $commentText = $val['text'] ?? '';

                            if ($commentId && $senderId !== $pageId && !empty($commentText) && $channel->is_bot_enabled) {
                                $lockKey = "ig_comment_lock_{$commentId}";
                                if (Cache::has($lockKey)) continue;
                                Cache::put($lockKey, true, now()->addMinutes(30));

                                $contact = Contact::firstOrCreate(
                                    ['office_id' => $officeId, 'ig_username' => $senderUsername],
                                    ['name' => "@{$senderUsername}", 'pipeline_stage' => 'lead']
                                );

                                $conversation = Conversation::firstOrCreate(
                                    ['office_id' => $officeId, 'channel_id' => $channel->id, 'contact_id' => $contact->id],
                                    ['channel_type' => 'ig_comment', 'is_bot_active' => true]
                                );

                                Message::create([
                                    'conversation_id'     => $conversation->id,
                                    'office_id'           => $officeId,
                                    'sender_type'         => 'customer',
                                    'message_type'        => 'text',
                                    'message_body'        => $commentText,
                                    'external_message_id' => $commentId,
                                    'is_read'             => false,
                                ]);

                                $conversation->update([
                                    'last_message_at' => now(),
                                    'unread_count'    => $conversation->unread_count + 1
                                ]);

                                $aiReply = $gemini->generateReply($conversation);
                                if ($aiReply) {
                                    $composio->replyInstagramComment($office, $commentId, $aiReply);

                                    Message::create([
                                        'conversation_id' => $conversation->id,
                                        'office_id'       => $officeId,
                                        'sender_type'     => 'bot',
                                        'message_type'    => 'text',
                                        'message_body'    => $aiReply,
                                        'is_read'         => true,
                                    ]);
                                }
                            }
                        }

                    }
                }
            }

            return response('EVENT_RECEIVED', 200);
        }

        return response('NOT_FOUND', 404);
    }
}
