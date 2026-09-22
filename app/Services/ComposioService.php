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

    /**
     * 1. Inisiasi Sesi OAuth Terisolasi per Kantor
     */
    public function initiateOAuth(Office $office, string $appName = 'facebook'): ?string
    {
        try {
            $toolkit = strtolower($appName) === 'instagram' ? 'instagram' : 'facebook';
            $userId  = "office_{$office->id}_{$office->slug}";

            $authConfigId = $this->getAuthConfigId($office, $toolkit);
            if (!$authConfigId) return null;

            // Redirect kembali ke halaman channel kantor tersebut
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

    /**
     * 2. EKSEKUTOR ACTION DENGAN ISOLASI KONEKSI KETAT (Strict Session Guard)
     */
    public function executeAction(Office $office, string $toolSlug, array $params = [], ?string $connectedAccountId = null): array
    {
        try {
            $userId = "office_{$office->id}_{$office->slug}";
            $arguments = empty($params) ? (object) [] : $params;

            $payload = [
                'user_id'   => $userId,
                'arguments' => $arguments,
                'version'   => 'latest',
            ];

            // 🔥 KUNCI UTAMA ISOLASI: Jika ada connected_account_id spesifik, kunci eksekusi ke akun tersebut!
            if ($connectedAccountId) {
                $payload['connected_account_id'] = $connectedAccountId;
            }

            $response = $this->client($office)->post("/tools/execute/{$toolSlug}", $payload);
            $json = $response->json();

            if ($response->successful() && ($json['successful'] ?? true) && empty($json['error'])) {
                return [
                    'success' => true,
                    'data'    => $json,
                ];
            }

            Log::error("Composio Action Failed [{$toolSlug}]:", [
                'office'               => $office->slug,
                'connected_account_id' => $connectedAccountId,
                'response'             => $json,
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
     * 3. BACKGROUND AUTO-SYNC: Menarik Sesi Aktif KHUSUS Kantor Ini & Mengunci Page ID
     */
    /**
     * 3. BACKGROUND AUTO-SYNC DENGAN DUKUNGAN MULTI-PAGE FACEBOOK (Anti Tertimpa)
     */
    public function autoSyncOfficeChannel(Office $office, string $platform = 'facebook'): ?Channel
    {
        try {
            $userId = "office_{$office->id}_{$office->slug}";
            $toolkitSlug = $platform === 'instagram' ? 'instagram' : 'facebook';

            // 1. Tarik akun terkoneksi KHUSUS user_id kantor ini
            $res = $this->client($office)->get('/connected_accounts', [
                'user_id' => $userId,
            ]);

            $items = $res->json('items') ?? $res->json('data') ?? [];
            if (empty($items)) return null;

            // Cari sesi yang aktif
            $matchedAccount = null;
            foreach ($items as $item) {
                $slug = $item['toolkit']['slug'] ?? $item['appName'] ?? '';
                $status = strtoupper($item['status'] ?? '');
                if (strtolower($slug) === $toolkitSlug && $status === 'ACTIVE') {
                    $matchedAccount = $item;
                    break;
                }
            }

            if (!$matchedAccount) return null;

            $connectionId = $matchedAccount['id']; // ca_xxxx milik kantor ini

            // 2. Ambil Channel di database
            $channel = Channel::firstOrCreate(
                ['office_id' => $office->id, 'type' => $platform],
                ['name' => strtoupper($platform) . ': ' . $office->name, 'is_bot_enabled' => true]
            );

            // Kunci ID Koneksi Composio ke channel kantor ini
            $channel->update([
                'composio_entity_id'     => $userId,
                'composio_connection_id' => $connectionId,
                'status'                 => 'connected',
            ]);

            // 3. JIKA FACEBOOK: TARIK SELURUH HALAMAN & COCOKKAN SECARA PINTAR
            if ($platform === 'facebook') {
                $pagesRes = $this->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES', [], $connectionId);
                $pagesData = $pagesRes['data']['data'] ?? $pagesRes['data']['response_data'] ?? [];
                $pages = isset($pagesData['data']) && is_array($pagesData['data']) ? $pagesData['data'] : (is_array($pagesData) ? $pagesData : []);

                if (!empty($pages)) {
                    $selectedPage = null;

                    // A. Prioritas 1: Jika Channel sudah memiliki Page ID yang valid, pastikan Page ID itu tetap dipertahankan
                    if (!empty($channel->identifier)) {
                        foreach ($pages as $p) {
                            if ((string)($p['id'] ?? '') === (string)$channel->identifier) {
                                $selectedPage = $p;
                                break;
                            }
                        }
                    }

                    // B. Prioritas 2: Jika belum ada, cocokkan nama Halaman dengan nama Kantor
                    if (!$selectedPage) {
                        $officeNameLower = strtolower($office->name);
                        foreach ($pages as $p) {
                            $pageNameLower = strtolower($p['name'] ?? '');
                            if (str_contains($pageNameLower, $officeNameLower) || str_contains($officeNameLower, $pageNameLower)) {
                                $selectedPage = $p;
                                break;
                            }
                        }
                    }

                    // C. Prioritas 3: Fallback ke halaman pertama hanya jika belum ada pilihan sama sekali
                    if (!$selectedPage) {
                        $selectedPage = $pages[0];
                    }

                    $pageId = $selectedPage['id'] ?? null;
                    $pageName = $selectedPage['name'] ?? null;

                    if ($pageId) {
                        $channel->update([
                            'identifier' => (string) $pageId,
                            'name'       => $pageName ? "FB: {$pageName}" : $channel->name,
                        ]);
                        Log::info("🎯 [FB Smart Page Matcher] Kantor: {$office->name} -> Halaman: {$pageName} (ID: {$pageId})");
                    }
                }
            }

            return $channel;
        } catch (\Throwable $e) {
            Log::error("Exception autoSyncOfficeChannel: " . $e->getMessage());
            return null;
        }
    }
    /**
     * 4. CEK KESEHATAN SESI REALTIME (HEALTH CHECKER)
     */
    public function checkConnectionHealth(Office $office, Channel $channel): bool
    {
        try {
            if (!$channel->composio_connection_id) {
                $channel->update(['status' => 'disconnected']);
                return false;
            }

            $res = $this->client($office)->get("/connected_accounts/{$channel->composio_connection_id}");

            if ($res->successful()) {
                $data = $res->json();
                $status = strtoupper($data['status'] ?? '');

                if ($status === 'ACTIVE') {
                    if ($channel->status !== 'connected') {
                        $channel->update(['status' => 'connected']);
                    }
                    return true;
                }
            }

            // Jika status EXPIRED atau DELETED, set disconnected
            $channel->update(['status' => 'disconnected']);
            Log::warning("⚠️ [Session Expired] Sesi Channel {$channel->name} ({$channel->id}) telah kedaluwarsa di Meta.");
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 5. Kirim Pesan Facebook Messenger (Terisolasi)
     */
    public function sendFacebookMessenger(Office $office, string $recipientId, string $message, ?string $pageId = null): array
    {
        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        $connectionId = $channel?->composio_connection_id;
        $pageId = $pageId ?: $channel?->identifier;

        return $this->executeAction($office, 'FACEBOOK_SEND_MESSAGE', [
            'page_id'      => (string) $pageId,
            'recipient_id' => (string) $recipientId,
            'message_text' => $message,
        ], $connectionId);
    }

    /**
     * 6. Balas Komentar Facebook (Terisolasi)
     */
    public function replyFacebookComment(Office $office, string $commentId, string $message): array
    {
        $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
        $connectionId = $channel?->composio_connection_id;

        return $this->executeAction($office, 'FACEBOOK_CREATE_COMMENT', [
            'object_id'    => (string) $commentId,
            'message'      => $message,
            'message_text' => $message,
        ], $connectionId);
    }

    /**
     * 7. Kirim DM Instagram (Terisolasi)
     */
    public function sendInstagramDm(Office $office, string $recipientId, string $message): array
    {
        $channel = Channel::where('office_id', $office->id)->where('type', 'instagram')->first();
        $connectionId = $channel?->composio_connection_id;

        return $this->executeAction($office, 'INSTAGRAM_SEND_TEXT_MESSAGE', [
            'recipient_id' => (string) $recipientId,
            'text'         => $message,
        ], $connectionId);
    }

    /**
     * 8. Balas Komentar Instagram (Terisolasi)
     */
    public function replyInstagramComment(Office $office, string $commentId, string $message): array
    {
        $channel = Channel::where('office_id', $office->id)->where('type', 'instagram')->first();
        $connectionId = $channel?->composio_connection_id;

        return $this->executeAction($office, 'INSTAGRAM_CREATE_MEDIA_COMMENT_REPLY', [
            'comment_id'   => (string) $commentId,
            'message'      => $message,
            'message_text' => $message,
        ], $connectionId);
    }
}
