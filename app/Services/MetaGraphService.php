<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphService
{
    protected string $graphUrl;

    public function __construct()
    {
        $this->graphUrl = config('services.meta.graph_url', 'https://graph.facebook.com/v19.0');
    }

    public function sendFacebookMessengerReply(string $pageAccessToken, string $recipientPsid, ?string $text = null, ?string $mediaUrl = null, string $mediaType = 'image')
    {
        // A. Jika ada File Media (Upload File Fisik Langsung ke Meta)
        if ($mediaUrl) {
            $relativePath = str_replace('/storage/', '', $mediaUrl);
            $localPath = storage_path('app/public/' . $relativePath);

            if (file_exists($localPath)) {
                $type = str_contains($mediaType, 'video') ? 'video' : (str_contains($mediaType, 'image') ? 'image' : 'file');

                $res = Http::attach(
                    'filedata', file_get_contents($localPath), basename($localPath)
                )->post("{$this->graphUrl}/me/messages?access_token={$pageAccessToken}", [
                    'recipient' => json_encode(['id' => $recipientPsid]),
                    'message' => json_encode([
                        'attachment' => [
                            'type' => $type,
                            'payload' => ['is_reusable' => true]
                        ]
                    ]),
                ]);

                Log::info("[FB Messenger Media Upload Respon] " . $res->body());
            }
        }

        // B. Jika ada Teks Pesan
        if (!empty(trim($text ?? ''))) {
            $res = Http::post("{$this->graphUrl}/me/messages?access_token={$pageAccessToken}", [
                'recipient' => ['id' => $recipientPsid],
                'message' => ['text' => $text],
            ]);
            Log::info("[FB Messenger Text Respon] " . $res->body());
            return $res->json();
        }

        return ['success' => true];
    }

    /**
     * 2. Kirim Pesan & Media ke Instagram DM
     */
    /**
     * 2. Kirim Pesan & Media ke Instagram DM (Metode Multipart Filedata Langsung)
     */
    public function sendInstagramDmReply(string $pageAccessToken, string $recipientIgid, ?string $text = null, ?string $mediaUrl = null, string $mediaType = 'image')
    {
        // A. Jika ada File Media (Upload File Fisik Langsung ke Instagram)
        if ($mediaUrl) {
            $relativePath = str_replace('/storage/', '', $mediaUrl);
            $localPath = storage_path('app/public/' . $relativePath);

            if (file_exists($localPath)) {
                $type = str_contains($mediaType, 'video') ? 'video' : 'image';

                $res = Http::attach(
                    'filedata', file_get_contents($localPath), basename($localPath)
                )->post("{$this->graphUrl}/me/messages?access_token={$pageAccessToken}", [
                    'recipient' => json_encode(['id' => $recipientIgid]),
                    'message' => json_encode([
                        'attachment' => [
                            'type' => $type,
                            'payload' => []
                        ]
                    ]),
                ]);

                Log::info("[Instagram DM Media Upload Respon] " . $res->body());
            }
        }

        // B. Jika ada Teks Pesan / Caption
        if (!empty(trim($text ?? ''))) {
            $res = Http::post("{$this->graphUrl}/me/messages?access_token={$pageAccessToken}", [
                'recipient' => ['id' => $recipientIgid],
                'message' => ['text' => $text],
            ]);
            Log::info("[Instagram DM Text Respon] " . $res->body());
            return $res->json();
        }

        return ['success' => true];
    }

    /**
     * 3. Balas Komentar di Postingan Facebook atau Instagram
     */
    public function replyToComment(string $pageAccessToken, string $commentId, string $replyText)
    {
        return Http::post("{$this->graphUrl}/{$commentId}/comments?access_token={$pageAccessToken}", [
            'message' => $replyText,
        ])->json();
    }



    /**
     * Daftarkan Webhook Otomatis (Membedakan Format Facebook vs Instagram)
     */
    /**
     * Daftarkan & Verifikasi Webhook Otomatis (Membedakan Facebook Page vs Instagram)
     */
    /**
     * Daftarkan Webhook Halaman Facebook atau Verifikasi Akun Instagram Bisnis
     */
    // public function subscribePageWebhook(string $identifier, string $pageAccessToken, string $type = 'facebook'): array
    // {
    //     try {
    //         // A. JIKA INSTAGRAM: Verifikasi Keaktifan Akun & Token Instagram
    //         if ($type === 'instagram') {
    //             $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$identifier}", [
    //                 'fields'       => 'id,name,username,profile_picture_url',
    //                 'access_token' => $pageAccessToken,
    //             ]);

    //             $data = $response->json();

    //             if ($response->successful() && !empty($data['id'])) {
    //                 Log::info("✅ [Meta IG Verified] Akun IG: @" . ($data['username'] ?? $data['name'] ?? $identifier) . " ({$identifier}) Valid!");
    //                 return [
    //                     'success' => true,
    //                     'data'    => $data,
    //                 ];
    //             }

    //             Log::error("❌ [Meta IG Verification Failed] ID: {$identifier}", $data);
    //             return [
    //                 'success' => false,
    //                 'error'   => $data['error'] ?? ['message' => 'Token Instagram tidak valid atau ID akun salah.'],
    //             ];
    //         }

    //         // B. JIKA FACEBOOK: Tembak Subscribed Apps ke Meta Graph API
    //         $endpoint = "https://graph.facebook.com/v21.0/{$identifier}/subscribed_apps";
    //         $response = Http::timeout(15)->post($endpoint, [
    //             'subscribed_fields' => 'messages,messaging_postbacks,feed,message_deliveries,message_reads,message_echoes',
    //             'access_token'      => $pageAccessToken,
    //         ]);

    //         $data = $response->json();

    //         if ($response->successful() && ($data['success'] ?? false)) {
    //             Log::info("✅ [Meta FB Subscribed] Halaman ID: {$identifier} Sukses!");
    //             return [
    //                 'success' => true,
    //                 'data'    => $data,
    //             ];
    //         }

    //         Log::error("❌ [Meta FB Subscribe Failed] Halaman ID: {$identifier}", $data);
    //         return [
    //             'success' => false,
    //             'error'   => $data['error'] ?? ['message' => 'Gagal mendaftarkan webhook Halaman Facebook.'],
    //         ];
    //     } catch (\Throwable $e) {
    //         Log::error("Exception subscribePageWebhook: " . $e->getMessage());
    //         return [
    //             'success' => false,
    //             'error'   => ['message' => $e->getMessage()],
    //         ];
    //     }
    // }

    /**
     * Daftarkan Webhook Resmi ke Meta Graph API (Mendukung Facebook & Instagram)
     */
    public function subscribePageWebhook(string $identifier, string $pageAccessToken, string $type = 'facebook'): array
    {
        try {
            $endpoint = "https://graph.facebook.com/v21.0/{$identifier}/subscribed_apps";

            // 🔥 KUNCI PERBAIKAN: FIELD KHUSUS INSTAGRAM VS FACEBOOK
            $fields = ($type === 'instagram')
                ? 'messages,messaging_postbacks,comments,message_reactions,messaging_seen'
                : 'messages,messaging_postbacks,feed,message_deliveries,message_reads,message_echoes';

            $response = Http::timeout(15)->post($endpoint, [
                'subscribed_fields' => $fields,
                'access_token'      => $pageAccessToken,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['success'] ?? false)) {
                Log::info("✅ [Meta " . strtoupper($type) . " Subscribed] ID: {$identifier} Sukses!");
                return [
                    'success' => true,
                    'data'    => $data,
                ];
            }

            Log::error("❌ [Meta " . strtoupper($type) . " Subscribe Failed] ID: {$identifier}", $data);
            return [
                'success' => false,
                'error'   => $data['error'] ?? ['message' => 'Gagal mendaftarkan webhook ke Meta.'],
            ];
        } catch (\Throwable $e) {
            Log::error("Exception subscribePageWebhook: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ];
        }
    }


    /**
     * 3. Balas Komentar di Postingan Facebook
     */
    public function replyToFacebookComment(string $pageAccessToken, string $commentId, string $replyText)
    {
        return Http::post("{$this->graphUrl}/{$commentId}/comments?access_token={$pageAccessToken}", [
            'message' => $replyText,
        ])->json();
    }

    /**
     * 4. Balas Komentar di Postingan Instagram
     */
    public function replyToInstagramComment(string $pageAccessToken, string $commentId, string $replyText)
    {
        return Http::post("{$this->graphUrl}/{$commentId}/replies?access_token={$pageAccessToken}", [
            'message' => $replyText,
        ])->json();
    }

    /**
     * 5. Pasang Komentar Pertama Otomatis (Auto First Comment) di Postingan FB
     */
    public function postFirstComment(string $pageAccessToken, string $postId, string $commentText)
    {
        return Http::post("{$this->graphUrl}/{$postId}/comments?access_token={$pageAccessToken}", [
            'message' => $commentText,
        ])->json();
    }


}
