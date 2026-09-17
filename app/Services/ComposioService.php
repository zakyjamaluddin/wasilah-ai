<?php

namespace App\Services;

use App\Models\Office;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComposioService
{
    /**
     * Base URL REST API Composio.dev Versi 3.1
     */
    protected string $baseUrl = 'https://backend.composio.dev/api/v3.1';

    /**
     * Client HTTP dengan Header API Key resmi Composio
     */
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

    /**
     * 1. DAPATKAN / AUTO-CREATE AUTH CONFIG ID UNTUK TOOLKIT (facebook / instagram)
     */
    /**
     * 1. DAPATKAN / AUTO-CREATE AUTH CONFIG ID UNTUK TOOLKIT (facebook / instagram)
     */
    public function getAuthConfigId(Office $office, string $toolkitSlug): ?string
    {
        try {
            $client = $this->client($office);
            $slug = strtolower($toolkitSlug);

            // 1. Cek apakah auth config sudah pernah dibuat di akun ini
            $response = $client->get('/auth_configs', [
                'toolkit_slug' => $slug,
            ]);

            if ($response->successful()) {
                $items = $response->json('items') ?? $response->json('data') ?? [];
                if (!empty($items) && isset($items[0]['id'])) {
                    return $items[0]['id'];
                }
            }

            // 2. Jika belum ada, buatkan Managed Auth Config baru dengan format objek resmi v3.1
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

            Log::error("Gagal membuat AuthConfig untuk {$slug}:", [
                'status' => $createRes->status(),
                'body'   => $createRes->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error("Exception getAuthConfigId ({$slug}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * 2. INSIASI SESI OAUTH COMPOSIO (FB PAGE & INSTAGRAM)
     * Menggunakan endpoint resmi: POST /api/v3.1/connected_accounts/link
     */
    public function initiateOAuth(Office $office, string $appName = 'facebook'): ?string
    {
        try {
            $toolkit = strtolower($appName) === 'instagram' ? 'instagram' : 'facebook';
            $userId  = "office_{$office->id}_{$office->slug}";

            // 1. Dapatkan Auth Config ID
            $authConfigId = $this->getAuthConfigId($office, $toolkit);

            if (!$authConfigId) {
                Log::error("Composio: AuthConfig ID tidak ditemukan untuk toolkit [{$toolkit}]");
                return null;
            }

            // 2. Buat Auth Link Session resmi di Composio v3.1
            $callbackUrl = url("/admin/{$office->slug}/channels");

            $response = $this->client($office)->post('/connected_accounts/link', [
                'auth_config_id' => $authConfigId,
                'user_id'        => $userId,
                'callback_url'   => $callbackUrl,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Composio Auth Link Created successfully for Office: {$office->slug}", $data);

                // Composio v3.1 mengembalikan 'redirect_url'
                return $data['redirect_url'] ?? $data['redirectUrl'] ?? $data['url'] ?? null;
            }

            Log::error('Composio initiateOAuth Failed:', [
                'office' => $office->slug,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Exception in Composio initiateOAuth: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 3. EKSEKUTOR TOOL / ACTION COMPOSIO v3.1
     * Menggunakan endpoint resmi: POST /api/v3.1/tools/execute/{tool_slug}
     */
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

            if ($response->successful() && ($json['successful'] ?? true)) {
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
                'error'   => $json['error'] ?? 'Gagal mengeksekusi aksi Composio.',
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
     * 4. Aksi Cepat: Balas Komentar Facebook
     */
    public function replyFacebookComment(Office $office, string $commentId, string $message): array
    {
        return $this->executeAction($office, 'FACEBOOK_CREATE_POST_COMMENT', [
            'comment_id' => $commentId,
            'message'    => $message,
        ]);
    }

    /**
     * 5. Aksi Cepat: Kirim DM Facebook Messenger
     */
    public function sendFacebookMessenger(Office $office, string $recipientId, string $message): array
    {
        return $this->executeAction($office, 'FACEBOOK_SEND_MESSAGE', [
            'recipient_id' => $recipientId,
            'message'      => $message,
        ]);
    }

    /**
     * 6. Aksi Cepat: Balas Komentar Instagram (Post / Reels)
     */
    public function replyInstagramComment(Office $office, string $commentId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_CREATE_MEDIA_COMMENT_REPLY', [
            'comment_id' => $commentId,
            'message'    => $message,
        ]);
    }

    /**
     * 7. Aksi Cepat: Kirim DM Instagram
     */
    public function sendInstagramDm(Office $office, string $recipientId, string $message): array
    {
        return $this->executeAction($office, 'INSTAGRAM_SEND_DIRECT_MESSAGE', [
            'recipient_id' => $recipientId,
            'message'      => $message,
        ]);
    }
}