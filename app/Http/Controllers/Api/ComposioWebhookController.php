<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Office;
use App\Services\ComposioService;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ComposioWebhookController extends Controller
{
    public function handle(Request $request, ComposioService $composio, GeminiAiService $gemini)
    {
        $payload = $request->all();
        Log::info('📥 [Composio Webhook Received]:', $payload);

        // 1. Ekstrak User ID / Entity ID (Mendukung user_id, userUuid, entity_id)
        $userId = $payload['user_id'] 
            ?? $payload['userUuid'] 
            ?? $payload['entity_id'] 
            ?? $payload['data']['user_id'] 
            ?? $payload['data']['userUuid'] 
            ?? null;

        if (!$userId) {
            Log::warning('⚠️ [Composio Webhook Ignored] user_id tidak ditemukan di payload.');
            return response()->json(['status' => 'ignored', 'reason' => 'user_id not found'], 200);
        }

        // Ekstrak Office ID dari format: office_{id}_{slug}
        preg_match('/office_(\d+)_/', $userId, $matches);
        $officeId = $matches[1] ?? null;

        if (!$officeId) {
            Log::warning("⚠️ [Composio Webhook] Pola office ID tidak cocok pada user_id: {$userId}");
            return response()->json(['status' => 'error', 'reason' => 'Invalid office pattern'], 400);
        }

        $office = Office::with('composioAccount')->find($officeId);
        if (!$office) {
            return response()->json(['status' => 'error', 'reason' => 'Office not found'], 404);
        }

        // 2. Verifikasi Signature (Jika Secret Diisi)
        $webhookSecret = $office->composioAccount?->webhook_secret;
        if ($webhookSecret && !$this->verifySignature($request, $webhookSecret)) {
            Log::warning("⛔ [Composio Webhook] Signature tidak valid untuk Kantor ID: {$officeId}");
            return response()->json(['status' => 'unauthorized', 'reason' => 'Invalid signature'], 401);
        }

        // 3. Deteksi Tipe Event & Platform (Facebook vs Instagram)
        $triggerName = strtoupper($payload['trigger_name'] ?? $payload['event'] ?? $payload['type'] ?? '');
        $data = $payload['data'] ?? $payload;
        $appName = strtolower($payload['appName'] ?? $payload['app'] ?? ($data['appName'] ?? ''));

        // Deteksi apakah event Facebook Messenger / DM
        $isFbMessage = str_contains($triggerName, 'FACEBOOK') && (str_contains($triggerName, 'MESSAGE') || str_contains($triggerName, 'MESSENGER'))
            || ($appName === 'facebook' && (isset($data['message']) || isset($data['messaging'])))
            || str_contains($triggerName, 'COMPOSIO.TRIGGER.MESSAGE');

        // Deteksi apakah Komentar Facebook
        $isFbComment = str_contains($triggerName, 'FACEBOOK') && str_contains($triggerName, 'COMMENT');

        // Deteksi apakah Instagram DM
        $isIgMessage = (str_contains($triggerName, 'INSTAGRAM') && str_contains($triggerName, 'MESSAGE'))
            || ($appName === 'instagram' && isset($data['message']));

        // Deteksi apakah Instagram Komentar
        $isIgComment = str_contains($triggerName, 'INSTAGRAM') && str_contains($triggerName, 'COMMENT');

        // ROUTER EKSEKUSI
        if ($isFbMessage) {
            $this->handleFacebookMessenger($data, $office, $composio, $gemini);
        } elseif ($isFbComment) {
            $this->handleFacebookComment($data, $office, $composio, $gemini);
        } elseif ($isIgMessage) {
            $this->handleInstagramDirectMessage($data, $office, $composio, $gemini);
        } elseif ($isIgComment) {
            $this->handleInstagramComment($data, $office, $composio, $gemini);
        } else {
            Log::info("ℹ️ [Composio Webhook Event Dilewati] Trigger: {$triggerName}, App: {$appName}");
        }

        return response()->json(['status' => 'success', 'message' => 'Processed via Composio Gateway']);
    }

    protected function verifySignature(Request $request, string $secret): bool
    {
        $signature = $request->header('x-composio-signature') 
            ?? $request->header('x-webhook-signature') 
            ?? $request->header('webhook-signature');

        if (!$signature) return true; // Fallback jika header tidak dikirimkan

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * 1. AUTO-REPLY MESSENGER DM (FACEBOOK)
     */
    protected function handleFacebookMessenger(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        // Ekstrak Page ID otomatis
        $pageId = $composio->getFacebookPageId($office);

        // Ekstrak Sender PSID dari berbagai format data Composio
        $senderId = $data['sender']['id'] 
            ?? $data['sender_id'] 
            ?? $data['from']['id'] 
            ?? $data['user_id'] 
            ?? ($data['messaging'][0]['sender']['id'] ?? null);

        // Ekstrak Teks Pesan
        $userText = $data['message']['text'] 
            ?? $data['text'] 
            ?? $data['message'] 
            ?? ($data['messaging'][0]['message']['text'] ?? '');

        $messageId = $data['message']['mid'] 
            ?? $data['id'] 
            ?? ($data['messaging'][0]['message']['mid'] ?? null);

        // Validasi: Jangan balas jika pesan kosong, atau jika berasal dari Page itu sendiri (echo)
        if (!$senderId || empty(trim($userText)) || ($data['is_echo'] ?? false) || ($data['message']['is_echo'] ?? false) || (string)$senderId === (string)$pageId) {
            return;
        }

        // Anti-Duplicate Lock
        if ($messageId) {
            $lockKey = "fb_comp_msg_{$messageId}";
            if (Cache::has($lockKey)) return;
            Cache::put($lockKey, true, now()->addMinutes(10));
        }

        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        // Simpan / Ambil Kontak Customer
        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'fb_user_id' => (string) $senderId],
            ['name' => 'FB User ' . substr($senderId, -4), 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'facebook', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        // Simpan Pesan Masuk ke Riwayat CRM
        Message::create([
            'office_id'           => $office->id,
            'conversation_id'     => $conversation->id,
            'sender_type'         => 'customer',
            'message_type'        => 'text',
            'message_body'        => $userText,
            'external_message_id' => $messageId,
        ]);

        // Generate Balasan AI Gemini Humanis
        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            // 🔥 Tembakkan Balasan ke Messenger via Composio
            $sendRes = $composio->sendFacebookMessenger($office, (string) $senderId, $aiReply, $pageId);
            Log::info("🚀 [Composio Messenger Auto-Reply Sent]:", $sendRes);

            // Simpan Balasan Bot ke CRM
            Message::create([
                'office_id'           => $office->id,
                'conversation_id'     => $conversation->id,
                'sender_type'         => 'bot',
                'message_type'        => 'text',
                'message_body'        => $aiReply,
            ]);

            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }

    /**
     * 2. AUTO-REPLY KOMENTAR FACEBOOK
     */
    protected function handleFacebookComment(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $commentId = $data['comment_id'] ?? $data['id'] ?? null;
        $userText = $data['message'] ?? $data['text'] ?? '';
        $senderName = $data['from']['name'] ?? 'User FB';
        $senderId = $data['from']['id'] ?? 'fb_user';

        if (!$commentId || empty($userText)) return;

        $lockKey = "fb_comp_comment_{$commentId}";
        if (Cache::has($lockKey)) return;
        Cache::put($lockKey, true, now()->addMinutes(30));

        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'fb_user_id' => (string) $senderId],
            ['name' => $senderName, 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'facebook', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        Message::create([
            'office_id'       => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type'     => 'customer',
            'message_type'    => 'text',
            'message_body'    => "[Komentar FB]: {$userText}",
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->replyFacebookComment($office, $commentId, $aiReply);

            Message::create([
                'office_id'       => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type'     => 'bot',
                'message_type'    => 'text',
                'message_body'    => $aiReply,
            ]);
        }
    }

    /**
     * 3. AUTO-REPLY INSTAGRAM DM
     */
    protected function handleInstagramDirectMessage(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $senderId = $data['sender']['id'] ?? $data['sender_id'] ?? $data['from']['id'] ?? null;
        $userText = $data['message']['text'] ?? $data['text'] ?? $data['message'] ?? '';
        $messageId = $data['message']['mid'] ?? $data['id'] ?? null;

        if (!$senderId || empty(trim($userText))) return;

        if ($messageId) {
            $lockKey = "ig_comp_msg_{$messageId}";
            if (Cache::has($lockKey)) return;
            Cache::put($lockKey, true, now()->addMinutes(10));
        }

        $channel = Channel::where('office_id', $office->id)->where('type', 'instagram')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'ig_username' => (string)$senderId],
            ['name' => 'IG User ' . substr($senderId, -4), 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'instagram', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        Message::create([
            'office_id'           => $office->id,
            'conversation_id'     => $conversation->id,
            'sender_type'         => 'customer',
            'message_type'        => 'text',
            'message_body'        => $userText,
            'external_message_id' => $messageId,
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->sendInstagramDm($office, (string)$senderId, $aiReply);

            Message::create([
                'office_id'           => $office->id,
                'conversation_id'     => $conversation->id,
                'sender_type'         => 'bot',
                'message_type'        => 'text',
                'message_body'        => $aiReply,
            ]);

            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }

    /**
     * 4. AUTO-REPLY INSTAGRAM KOMENTAR
     */
    protected function handleInstagramComment(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $commentId = $data['comment_id'] ?? $data['id'] ?? null;
        $userText = $data['text'] ?? $data['message'] ?? '';
        $username = $data['from']['username'] ?? 'ig_user';

        if (!$commentId || empty($userText)) return;

        $lockKey = "ig_comp_comment_{$commentId}";
        if (Cache::has($lockKey)) return;
        Cache::put($lockKey, true, now()->addMinutes(30));

        $channel = Channel::where('office_id', $office->id)->where('type', 'instagram')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'ig_username' => $username],
            ['name' => '@' . $username, 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'instagram', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        Message::create([
            'office_id'       => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type'     => 'customer',
            'message_type'    => 'text',
            'message_body'    => "[Komentar IG]: {$userText}",
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->replyInstagramComment($office, $commentId, $aiReply);

            Message::create([
                'office_id'       => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type'     => 'bot',
                'message_type'    => 'text',
                'message_body'    => $aiReply,
            ]);
        }
    }
}