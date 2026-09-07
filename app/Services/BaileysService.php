<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaileysService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.baileys.url', env('BAILEYS_API_URL', 'http://localhost:3000'));
        $this->apiKey = config('services.baileys.key', env('BAILEYS_API_KEY', 'rahasia-super-aman-123'));
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15);
    }

    // 1. Inisialisasi / Mulai Session Kantor
    public function startSession(string $sessionId)
    {
        return $this->client()->post('/api/session/start', [
            'sessionId' => $sessionId,
        ])->json();
    }

    // 2. Cek Status Session & Ambil Gambar QR
    public function getSessionStatus(string $sessionId)
    {
        return $this->client()->get("/api/session/status/{$sessionId}")->json();
    }

    // 3. Logout / Hapus Session
    public function logoutSession(string $sessionId)
    {
        return $this->client()->delete("/api/session/logout/{$sessionId}")->json();
    }

    // 4. Kirim Pesan Teks
    public function sendTextMessage(string $sessionId, string $to, string $text)
    {
        return $this->client()->post('/api/message/send-text', [
            'sessionId' => $sessionId,
            'to' => $to,
            'text' => $text,
        ])->json();
    }

    // 5. Kirim Media / Gambar
    public function sendMediaMessage(string $sessionId, string $to, string $mediaBase64, ?string $caption = null, string $mimeType = 'image/jpeg', ?string $fileName = null)
    {
        return $this->client()->post('/api/message/send-media', [
            'sessionId' => $sessionId,
            'to' => $to,
            'media' => $mediaBase64,
            'caption' => $caption,
            'mimeType' => $mimeType,
            'fileName' => $fileName,
        ])->json();
    }

    // 6. Tarik Daftar Grup WA
    public function getGroups(string $sessionId)
    {
        return $this->client()->get("/api/contacts/groups/{$sessionId}")->json();
    }

    // 7. Tarik Member Grup WA
    public function getGroupMembers(string $sessionId, string $groupId)
    {
        return $this->client()->get("/api/contacts/groups/{$sessionId}/{$groupId}/members")->json();
    }

    // 8. Tarik Semua Kontak Buku Telepon
    public function getPhonebook(string $sessionId)
    {
        return $this->client()->get("/api/contacts/phonebook/{$sessionId}")->json();
    }

    // 9. Eksekusi Broadcast Campaign
    public function dispatchBroadcast(string $sessionId, array $recipients, string $message, ?string $mediaUrl = null)
    {
        return $this->client()->post('/api/broadcast/dispatch', [
            'sessionId' => $sessionId,
            'recipients' => $recipients,
            'message' => $message,
            'mediaUrl' => $mediaUrl,
        ])->json();
    }
}
