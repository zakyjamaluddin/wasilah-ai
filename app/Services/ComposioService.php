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

            $response = $this->client($office)->post("/tools/execute/{$toolSlug}", [
                'user_id'   => $userId,
                'arguments' => $params,
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
     * Helper Cerdas: Dapatkan Page ID Fanspage Facebook yang Terhubung
     */
    public function getFacebookPageId(Office $office): ?string
    {
        // 1. Cek apakah sudah tersimpan di channel database
        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        if ($channel && !empty($channel->identifier) && is_numeric($channel->identifier)) {
            return $channel->identifier;
        }

        // 2. Jika belum, tarik daftar Halaman dari Composio
        $res = $this->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES');
        if ($res['success'] && !empty($res['data']['data'])) {
            $pages = $res['data']['data'];
            $firstPage = $pages[0] ?? null;
            $pageId = $firstPage['id'] ?? null;

            if ($pageId && $channel) {
                $channel->update(['identifier' => $pageId]);
            }
            return $pageId;
        }

        return null;
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
    public function sendInstagramDm(Office $office, string $recipientId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_SEND_DIRECT_MESSAGE', [
            'recipient_id' => (string) $recipientId,
            'message_text' => $message,
            'message'      => $message,
        ]);
    }

    /**
     * 4. Balas Komentar Instagram
     */
    public function replyInstagramComment(Office $office, string $commentId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_CREATE_MEDIA_COMMENT_REPLY', [
            'comment_id'   => (string) $commentId,
            'message'      => $message,
            'message_text' => $message,
        ]);
    }
}