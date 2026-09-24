<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiAiService;
use App\Services\MetaGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MetaWebhookController extends Controller
{
    /**
     * 1. Handshake Verifikasi Webhook Meta (GET)
     */
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
     * 2. Penangkap Event DM & Komentar FB / IG (POST) ➡️ Dibalas Langsung oleh MetaGraphService
     */
    public function handle(Request $request, MetaGraphService $meta, GeminiAiService $gemini)
    {
        $body = $request->all();
        Log::info("=== [META INBOUND EVENT] ===");
        Log::info(json_encode($body, JSON_PRETTY_PRINT));

        $object = $body['object'] ?? '';

        if ($object === 'page' || $object === 'instagram') {
            foreach ($body['entry'] as $entry) {
                $pageId = (string) ($entry['id'] ?? '');

                // Cari Channel Kantor di database berdasarkan Identifier Page ID
                $channel = Channel::where('identifier', $pageId)->first();
                if (!$channel || !$channel->is_bot_enabled) continue;

                $credentials = $channel->credentials ?? [];
                $accessToken = $credentials['access_token'] ?? null;
                $officeId = $channel->office_id;

                if (!$accessToken) {
                    Log::warning("⚠️ Channel [{$channel->name}] belum memiliki access_token di credentials.");
                    continue;
                }

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

                        // 🛡️ DOWNLOAD MEDIA JIKA ADA
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

                        $channelType = $object === 'instagram' ? 'ig_dm' : 'fb_dm';

                        // 1. Kontak
                        $contact = Contact::firstOrCreate(
                            ['office_id' => $officeId, 'fb_user_id' => $senderId],
                            ['name' => ($object === 'instagram' ? 'IG User ' : 'FB User ') . substr($senderId, -4), 'pipeline_stage' => 'lead']
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

                        // 4. BALAS OTOMATIS OLEH AI GEMINI VIA METAGRAPHSERVICE
                        if ($conversation->is_bot_active) {
                            $localImagePath = null;
                            if ($messageType === 'image' && $mediaUrl) {
                                $relativePath = str_replace('/storage/', '', $mediaUrl);
                                $localImagePath = storage_path('app/public/' . $relativePath);
                            }

                            $botReply = $gemini->generateReply($conversation, $localImagePath);

                            if ($botReply) {
                                if ($object === 'instagram') {
                                    $meta->sendInstagramDmReply($accessToken, $senderId, $botReply);
                                } else {
                                    $meta->sendFacebookMessengerReply($accessToken, $senderId, $botReply);
                                }

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
                            $firstCommentText = $credentials['auto_first_comment'] ?? null;

                            if ($postId && $firstCommentText) {
                                $meta->postFirstComment($accessToken, (string)$postId, $firstCommentText);
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
                                    $meta->replyToFacebookComment($accessToken, $commentId, $aiReply);

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
                                    $meta->replyToInstagramComment($accessToken, $commentId, $aiReply);

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