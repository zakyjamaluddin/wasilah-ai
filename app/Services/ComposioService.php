<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComposioService
{
    protected string $baseUrl = 'https://backend.composio.dev/api/v3.1';

    protected function client(Office $office)
    {
        $composioAccount = $office->composioAccount;

        if (!$composioAccount || empty($composioAccount->api_key)) {
            throw new \Exception("Kantor [{$office->name}] belum memilih Akun Composio yang aktif.");
        }

        return Http::withHeaders([
            'x-api-key'    => trim($composioAccount->api_key),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->baseUrl($this->baseUrl)->timeout(30);
    }

    public function getAuthConfigId(Office $office, string $toolkitSlug): ?string
    {
        try {
            $client = $this->client($office);
            $slug = strtolower($toolkitSlug);

            $response = $client->get('/auth_configs', [
                'toolkit_slug' => $slug,
            ]);

            if ($response->successful()) {
                $items = $response->json('items') ?? $response->json('data') ?? [];
                if (!empty($items) && isset($items[0]['id'])) {
                    return $items[0]['id'];
                }
            }

            $createRes = $client->post('/auth_configs', [
                'toolkit' => [
                    'slug' => $slug,
                ],
                'auth_config' => [
                    'type' => 'use_composio_managed_auth',
                ],
            ]);

            if ($createRes->successful()) {
                $created = $createRes->json();
                return $created['auth_config']['id'] ?? $created['id'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Exception getAuthConfigId ({$toolkitSlug}): " . $e->getMessage());
            return null;
        }
    }

    public function initiateOAuth(Office $office, string $appName = 'facebook'): ?string
    {
        try {
            $toolkit = strtolower($appName) === 'instagram' ? 'instagram' : 'facebook';
            $userId  = "office_{$office->id}_{$office->slug}";

            $authConfigId = $this->getAuthConfigId($office, $toolkit);
            if (!$authConfigId) return null;

            $callbackUrl = url("/admin/{$office->slug}/channels");

            $response = $this->client($office)->post('/connected_accounts/link', [
                'auth_config_id' => $authConfigId,
                'user_id'        => $userId,
                'callback_url'   => $callbackUrl,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['redirect_url'] ?? $data['redirectUrl'] ?? $data['url'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Exception in Composio initiateOAuth: " . $e->getMessage());
            return null;
        }
    }

    public function executeAction(Office $office, string $toolSlug, array $params = []): array
    {
        try {
            $userId = "office_{$office->id}_{$office->slug}";

            // 🔥 KUNCI PERBAIKAN: Jika params kosong, jadikan JSON Object {} (bukan array [])
            $arguments = empty($params) ? (object) [] : $params;

            $response = $this->client($office)->post("/tools/execute/{$toolSlug}", [
                'user_id'   => $userId,
                'arguments' => $arguments,
                'version'   => 'latest',
            ]);

            $json = $response->json();

            if ($response->successful() && ($json['successful'] ?? true) && empty($json['error'])) {
                return [
                    'success' => true,
                    'data'    => $json,
                ];
            }

            Log::error("Composio Action Failed [{$toolSlug}]:", [
                'office'   => $office->slug,
                'response' => $json,
            ]);

            return [
                'success' => false,
                'error'   => $json['error'] ?? $json['message'] ?? 'Gagal mengeksekusi aksi Composio.',
            ];
        } catch (\Throwable $e) {
            Log::error("Exception Composio Action [{$toolSlug}]: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }



    /**
     * 1. Kirim DM Facebook Messenger
     */
    public function sendFacebookMessenger(Office $office, string $recipientId, string $message, ?string $pageId = null): array
    {
        $pageId = $pageId ?: $this->getFacebookPageId($office);

        if (!$pageId) {
            return [
                'success' => false,
                'error'   => 'Facebook Page ID belum ditemukan. Pastikan akun FB sudah terhubung.',
            ];
        }

        return $this->executeAction($office, 'FACEBOOK_SEND_MESSAGE', [
            'page_id'      => (string) $pageId,
            'recipient_id' => (string) $recipientId,
            'message_text' => $message,
        ]);
    }

    /**
     * 2. Balas Komentar Facebook Post
     */
    public function replyFacebookComment(Office $office, string $commentId, string $message): array
    {
        return $this->executeAction($office, 'FACEBOOK_CREATE_COMMENT', [
            'object_id'    => (string) $commentId,
            'message'      => $message,
            'message_text' => $message,
        ]);
    }

    /**
     * 3. Kirim DM Instagram
     */
   /**
     * Cari PSID Asli Pengirim Instagram dari Obrolan Terakhir
     */
    /**
     * Cari PSID Asli Pengirim Instagram dari Obrolan Terakhir
     */
    public function getLatestInstagramSender(Office $office): ?array
    {
        try {
            // 1. Ambil daftar percakapan
            $convs = $this->executeAction($office, 'INSTAGRAM_LIST_ALL_CONVERSATIONS');
            $convItems = $convs['data']['data']['data'] ?? $convs['data']['data'] ?? [];

            if (empty($convItems)) return null;

            $latestConvId = $convItems[0]['id'] ?? null;
            if (!$latestConvId) return null;

            // 2. Ambil butir pesan terakhir dari percakapan tersebut
            $messagesRes = $this->executeAction($office, 'INSTAGRAM_LIST_ALL_MESSAGES', [
                'conversation_id' => $latestConvId,
            ]);

            $messages = $messagesRes['data']['data']['data'] ?? $messagesRes['data']['data'] ?? [];
            if (empty($messages)) return null;

            $latestMsg = $messages[0] ?? [];

            // Pengirim (Customer)
            $senderPsid = $latestMsg['from']['id'] ?? null;
            $senderUsername = $latestMsg['from']['username'] ?? 'User';

            // Penerima (Akun IG Bisnis Saya)
            $myIgData = $latestMsg['to']['data'][0] ?? [];
            $myIgUsername = $myIgData['username'] ?? 'Akun Saya';
            $myIgId = $myIgData['id'] ?? '-';

            $messageMid = $latestMsg['id'] ?? null;
            $messageText = $latestMsg['message'] ?? '';

            if ($senderPsid) {
                // 🔥 LOG TRANSPARAN & LENGKAP
                Log::info("==================================================");
                Log::info("🎯 [IG REAL PSID & ACCOUNT DETECTED]");
                Log::info("🏢 Akun IG Saya (Toko) : @{$myIgUsername} (ID: {$myIgId})");
                Log::info("👤 Pengirim (Sender)   : @{$senderUsername} (PSID: {$senderPsid})");
                Log::info("✉️ Pesan Terakhir      : \"{$messageText}\"");
                Log::info("🔑 Message ID (MID)    : {$messageMid}");
                Log::info("==================================================");

                return [
                    'psid'            => (string) $senderPsid,
                    'sender_username' => $senderUsername,
                    'my_ig_username'  => $myIgUsername,
                    'my_ig_id'        => $myIgId,
                    'mid'             => $messageMid,
                    'text'            => $messageText,
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Exception getLatestInstagramSender: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 3. Kirim DM Instagram (Direct Message)
     */
    public function sendInstagramDm(Office $office, string $recipientId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_SEND_TEXT_MESSAGE', [
            'recipient_id' => (string) $recipientId,
            'text'         => $message,
        ]);
    }

    /**
     * 4. Balas Komentar Instagram
     */
    public function replyInstagramComment(Office $office, string $commentId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_POST_IG_COMMENT_REPLIES', [
            'ig_comment_id'   => (string) $commentId,
            'message'      => $message,
            'message_text' => $message,
        ]);
    }


    /**
     * SINKRONISASI OTOMATIS: Tarik seluruh Fanspage Facebook & Update Channel di DB
     */
    public function syncFacebookPages(Office $office, ?Channel $channel = null): array
    {
        try {
            // Eksekusi tool FACEBOOK_LIST_MANAGED_PAGES ke Composio
            $res = $this->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES');

            if (!$res['success']) {
                return [
                    'success' => false,
                    'error'   => $res['error'] ?? 'Gagal menarik daftar Fanspage dari Facebook.',
                ];
            }

            // Ekstrak array pages dari berbagai kemungkinan nesting format Composio
            $data = $res['data']['data'] ?? $res['data']['response_data'] ?? $res['data'] ?? [];
            $pages = isset($data['data']) && is_array($data['data']) ? $data['data'] : (is_array($data) ? $data : []);

            if (empty($pages)) {
                return [
                    'success' => false,
                    'error'   => 'Tidak ada Fanspage Facebook yang ditemukan pada akun ini.',
                ];
            }

            // Ambil data Halaman Facebook pertama yang dikelola
            $firstPage = $pages[0] ?? [];
            $pageId    = $firstPage['id'] ?? null;
            $pageName  = $firstPage['name'] ?? null;

            if (!$pageId) {
                return [
                    'success' => false,
                    'error'   => 'Page ID tidak ditemukan di dalam respon Facebook.',
                ];
            }

            // Cari atau update Channel Facebook kantor ini
            $targetChannel = $channel ?: Channel::where('office_id', $office->id)->where('type', 'facebook')->first();

            if ($targetChannel) {
                $targetChannel->update([
                    'identifier' => (string) $pageId,
                    'name'       => $pageName ? "FB: {$pageName}" : $targetChannel->name,
                    'status'     => 'connected',
                ]);
            }

            Log::info("✅ [Auto-Sync FB Page Berhasil] Office: {$office->slug}, Page: {$pageName} ({$pageId})");

            return [
                'success'   => true,
                'page_id'   => (string) $pageId,
                'page_name' => $pageName,
                'channel'   => $targetChannel,
            ];
        } catch (\Throwable $e) {
            Log::error("Exception syncFacebookPages: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Helper Cerdas: Dapatkan Facebook Page ID (Auto-Sync jika belum ada)
     */
    public function getFacebookPageId(Office $office): ?string
    {
        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();

        // 1. Jika sudah ada di database, langsung pakai
        if ($channel && !empty($channel->identifier) && is_numeric($channel->identifier)) {
            return $channel->identifier;
        }

        // 2. Jika belum ada, jalankan auto-sync ke Composio
        $syncResult = $this->syncFacebookPages($office, $channel);
        return $syncResult['success'] ? $syncResult['page_id'] : null;
    }
}
