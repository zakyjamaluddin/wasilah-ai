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
    /**
     * Menerima event trigger real-time dari Composio.dev
     */
    public function handle(Request $request, ComposioService $composio, GeminiAiService $gemini)
    {
        $payload = $request->all();
        Log::info('📥 [Composio Webhook Received]', $payload);

        // 1. Ekstrak entity_id / userUuid (Format kita: office_{id}_{slug})
        $entityId = $payload['userUuid'] ?? $payload['entity_id'] ?? $payload['data']['userUuid'] ?? null;
        $event = $payload['trigger_name'] ?? $payload['event'] ?? $payload['type'] ?? null;

        if (!$entityId) {
            return response()->json(['status' => 'ignored', 'reason' => 'userUuid/entityId not found'], 200);
        }

        // 2. Cari Office ID berdasarkan pola regex 'office_{id}_'
        preg_match('/office_(\d+)_/', $entityId, $matches);
        $officeId = $matches[1] ?? null;

        if (!$officeId) {
            return response()->json(['status' => 'error', 'reason' => 'Invalid entityId pattern'], 400);
        }

        $office = Office::find($officeId);
        if (!$office) {
            return response()->json(['status' => 'error', 'reason' => 'Office not found'], 404);
        }
        // 🔥 2. Validasi Signature Keamanan Menggunakan Secret Akun Composio Terkait
        $webhookSecret = $office->composioAccount?->webhook_secret;
        if ($webhookSecret && !$this->verifySignature($request, $webhookSecret)) {
            Log::warning("⛔ [Composio Webhook] Signature tidak valid untuk Kantor ID: {$officeId}");
            return response()->json(['status' => 'unauthorized', 'reason' => 'Invalid signature'], 401);
        }
        // Data isi pesan / trigger
        $data = $payload['data'] ?? $payload;

        // 3. Router Event Composio (FB Page Post, FB Comment, FB DM, IG Comment, IG DM)
        switch (strtoupper($event)) {
            // A. FACEBOOK EVENTS
            case 'FACEBOOK_NEW_FEED_POST':
            case 'FACEBOOK_NEW_POST':
                $this->handleFacebookFirstComment($data, $office, $composio, $gemini);
                break;

            case 'FACEBOOK_COMMENT_RECEIVED':
            case 'FACEBOOK_NEW_COMMENT':
            case 'FACEBOOK_FEED_COMMENT':
                $this->handleFacebookComment($data, $office, $composio, $gemini);
                break;

            case 'FACEBOOK_MESSAGE_RECEIVED':
            case 'FACEBOOK_MESSENGER_MESSAGE':
                $this->handleFacebookMessenger($data, $office, $composio, $gemini);
                break;

            // B. INSTAGRAM EVENTS
            case 'INSTAGRAM_COMMENT_RECEIVED':
            case 'INSTAGRAM_MEDIA_COMMENT':
                $this->handleInstagramComment($data, $office, $composio, $gemini);
                break;

            case 'INSTAGRAM_MESSAGE_RECEIVED':
            case 'INSTAGRAM_DIRECT_MESSAGE':
                $this->handleInstagramDirectMessage($data, $office, $composio, $gemini);
                break;

            default:
                Log::info("ℹ️ Event Composio [{$event}] dilewati/tidak ditangani.");
                break;
        }

        return response()->json(['status' => 'success', 'message' => 'Event processed successfully']);
    }


    /**
     * Verifikasi Keaslian Signature Webhook dari Composio (HMAC SHA256)
     */
    protected function verifySignature(Request $request, ?string $webhookSecret): bool
    {
        if (empty($webhookSecret)) {
            // Jika secret belum diisi di dashboard admin, lewati verifikasi (fallback)
            return true;
        }

        $signatureHeader = $request->header('x-composio-signature') 
            ?? $request->header('x-webhook-signature')
            ?? $request->header('webhook-signature');

        if (!$signatureHeader) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signatureHeader);
    }

    /**
     * 1. AUTO FIRST COMMENT DI POSTINGAN BARU FACEBOOK
     */
    protected function handleFacebookFirstComment(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $postId = $data['post_id'] ?? $data['id'] ?? null;
        $postContent = $data['message'] ?? $data['description'] ?? '';

        if (!$postId) return;

        // Anti-Duplicate Lock (1 jam)
        $lockKey = "fb_first_comment_{$postId}";
        if (Cache::has($lockKey)) return;
        Cache::put($lockKey, true, now()->addHours(1));

        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        // Minta Gemini menyusun First Comment promosi & sapaan hangat
        $conversationFake = new Conversation([
            'office_id' => $office->id,
            'channel_type' => 'facebook',
        ]);
        
        $prompt = "Ini adalah postingan baru di Facebook kami: \"{$postContent}\". Buatkan 1 komentar pertama yang ramah, mengajak interaksi/diskusi, dan menyisipkan ajakan kontak WhatsApp jika ingin info lebih lanjut.";
        $aiReply = $gemini->generateFollowUpMessage($conversationFake, $prompt);

        if ($aiReply) {
            $composio->replyFacebookComment($office, $postId, $aiReply);
            Log::info("✅ [Auto First Comment FB Berhasil]: {$postId}");
        }
    }

    /**
     * 2. AUTO-REPLY KOMENTAR FACEBOOK
     */
    protected function handleFacebookComment(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $commentId = $data['comment_id'] ?? $data['id'] ?? null;
        $userText = $data['message'] ?? $data['text'] ?? '';
        $senderName = $data['from']['name'] ?? $data['sender_name'] ?? 'Pengguna Facebook';
        $senderId = $data['from']['id'] ?? $data['sender_id'] ?? 'fb_user';

        // Cegah membalas komentar bot sendiri
        if (!$commentId || empty($userText) || ($data['from']['is_self'] ?? false)) return;

        // Anti-Duplicate Guard
        $lockKey = "fb_comment_replied_{$commentId}";
        if (Cache::has($lockKey)) return;
        Cache::put($lockKey, true, now()->addMinutes(30));

        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        // Rekam / Ambil Kontak Prospek
        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'fb_user_id' => $senderId],
            ['name' => $senderName, 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'facebook', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        // Simpan komentar customer ke riwayat CRM
        Message::create([
            'office_id' => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message_body' => "[Komentar FB]: {$userText}",
        ]);

        // Generate Balasan AI Gemini Humanis
        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->replyFacebookComment($office, $commentId, $aiReply);

            // Simpan balasan bot ke riwayat CRM
            Message::create([
                'office_id' => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message_body' => $aiReply,
            ]);

            // Auto Score Lead
            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }

    /**
     * 3. AUTO-REPLY MESSENGER DM (FACEBOOK)
     */
    protected function handleFacebookMessenger(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $senderId = $data['sender']['id'] ?? $data['sender_id'] ?? null;
        $userText = $data['message']['text'] ?? $data['text'] ?? '';
        $messageId = $data['message']['mid'] ?? $data['id'] ?? null;

        if (!$senderId || empty($userText) || ($data['is_echo'] ?? false)) return;

        // Anti-Duplicate Lock
        if ($messageId) {
            $lockKey = "fb_msg_{$messageId}";
            if (Cache::has($lockKey)) return;
            Cache::put($lockKey, true, now()->addMinutes(10));
        }

        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'fb_user_id' => $senderId],
            ['name' => 'FB User ' . substr($senderId, -4), 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'facebook', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        Message::create([
            'office_id' => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message_body' => $userText,
            'external_message_id' => $messageId,
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->sendFacebookMessenger($office, $senderId, $aiReply);

            Message::create([
                'office_id' => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message_body' => $aiReply,
            ]);

            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }

    /**
     * 4. AUTO-REPLY KOMENTAR INSTAGRAM (POST / REELS)
     */
    protected function handleInstagramComment(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $commentId = $data['comment_id'] ?? $data['id'] ?? null;
        $userText = $data['text'] ?? $data['message'] ?? '';
        $username = $data['from']['username'] ?? $data['username'] ?? 'ig_user';

        if (!$commentId || empty($userText) || ($data['from']['is_self'] ?? false)) return;

        $lockKey = "ig_comment_replied_{$commentId}";
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
            'office_id' => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message_body' => "[Komentar IG]: {$userText}",
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->replyInstagramComment($office, $commentId, $aiReply);

            Message::create([
                'office_id' => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message_body' => $aiReply,
            ]);

            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }

    /**
     * 5. AUTO-REPLY INSTAGRAM DIRECT MESSAGE (DM)
     */
    protected function handleInstagramDirectMessage(array $data, Office $office, ComposioService $composio, GeminiAiService $gemini)
    {
        $senderId = $data['sender']['id'] ?? $data['sender_id'] ?? null;
        $userText = $data['message']['text'] ?? $data['text'] ?? '';
        $messageId = $data['message']['mid'] ?? $data['id'] ?? null;

        if (!$senderId || empty($userText) || ($data['is_echo'] ?? false)) return;

        if ($messageId) {
            $lockKey = "ig_dm_{$messageId}";
            if (Cache::has($lockKey)) return;
            Cache::put($lockKey, true, now()->addMinutes(10));
        }

        $channel = Channel::where('office_id', $office->id)->where('type', 'instagram')->first();
        if (!$channel || !$channel->is_bot_enabled) return;

        $contact = Contact::firstOrCreate(
            ['office_id' => $office->id, 'ig_username' => $senderId],
            ['name' => 'IG User ' . substr($senderId, -4), 'pipeline_stage' => 'Cold']
        );

        $conversation = Conversation::firstOrCreate(
            ['office_id' => $office->id, 'contact_id' => $contact->id, 'channel_id' => $channel->id],
            ['channel_type' => 'instagram', 'is_bot_active' => true, 'last_message_at' => now()]
        );

        Message::create([
            'office_id' => $office->id,
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'message_type' => 'text',
            'message_body' => $userText,
            'external_message_id' => $messageId,
        ]);

        $aiReply = $gemini->generateReply($conversation);

        if ($aiReply) {
            $composio->sendInstagramDm($office, $senderId, $aiReply);

            Message::create([
                'office_id' => $office->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message_type' => 'text',
                'message_body' => $aiReply,
            ]);

            $gemini->analyzeAndSummarizeLead($conversation);
        }
    }
}