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
     * Daftarkan Webhook Halaman Facebook Otomatis (Subscribed Apps)
     */
    public function subscribePageWebhook(string $pageId, string $pageAccessToken): array
    {
        try {
            $response = Http::post("{$this->graphUrl}/{$pageId}/subscribed_apps", [
                'subscribed_fields' => 'feed,messages',
                'access_token' => $pageAccessToken,
            ]);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("[Meta Webhook Subscribe Exception] " . $e->getMessage());
            return ['error' => ['message' => $e->getMessage()]];
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
