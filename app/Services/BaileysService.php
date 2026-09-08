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
        $this->baseUrl = config('services.baileys.url', env('BAILEYS_API_URL', 'https://vps.wasilah-ai.my.id'));
        $this->apiKey = config('services.baileys.key', env('BAILEYS_API_KEY', 'rahasia-super-aman-123'));
    }

    public function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(8)       // Batas tunggu 8 detik
            ->connectTimeout(5); // Batas koneksi 5 detik
    }

    // 1. Inisialisasi Session
    public function startSession(string $sessionId)
    {
        try {
            return $this->client()->post('/api/session/start', ['sessionId' => $sessionId])->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("[Baileys startSession Timeout] " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // 2. 🔥 CEK STATUS DENGAN PENGAMAN (ANTI-CRASH TIMEOUT)
    public function getSessionStatus(string $sessionId)
    {
        try {
            $response = $this->client()->get("/api/session/status/{$sessionId}");
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            return ['data' => ['status' => 'connecting', 'qrImage' => null]];
        } catch (\Exception $e) {
            // Jika VPS sedang sibuk enkripsi, kembalikan status connecting agar Livewire tidak error merah
            Log::info("[Baileys getStatus Waiting] Sedang memproses enkripsi di VPS...");
            return ['data' => ['status' => 'connecting', 'qrImage' => null]];
        }
    }

    // 3. Logout Session
    public function logoutSession(string $sessionId)
    {
        try {
            return $this->client()->delete("/api/session/logout/{$sessionId}")->json() ?? [];
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }

    // 4. Kirim Pesan Teks
    public function sendTextMessage(string $sessionId, string $to, string $text)
    {
        try {
            return $this->client()->post('/api/message/send-text', [
                'sessionId' => $sessionId,
                'to' => $to,
                'text' => $text,
            ])->json() ?? [];
        } catch (\Exception $e) {
            Log::error("[Baileys sendText Error] " . $e->getMessage());
            return ['status' => 'error'];
        }
    }

    // 5. Kirim Media / Gambar / Dokumen
    public function sendMediaMessage(string $sessionId, string $to, string $mediaBase64, ?string $caption = null, string $mimeType = 'image/jpeg', ?string $fileName = null)
    {
        try {
            return $this->client()->post('/api/message/send-media', [
                'sessionId' => $sessionId,
                'to' => $to,
                'media' => $mediaBase64,
                'caption' => $caption,
                'mimeType' => $mimeType,
                'fileName' => $fileName,
            ])->json() ?? [];
        } catch (\Exception $e) {
            Log::error("[Baileys sendMedia Error] " . $e->getMessage());
            return ['status' => 'error'];
        }
    }

    // 6. Tarik Grup
    public function getGroups(string $sessionId)
    {
        try {
            return $this->client()->get("/api/contacts/groups/{$sessionId}")->json() ?? [];
        } catch (\Exception $e) {
            return ['data' => []];
        }
    }

    // 7. Tarik Member Grup
    public function getGroupMembers(string $sessionId, string $groupId)
    {
        try {
            return $this->client()->get("/api/contacts/groups/{$sessionId}/{$groupId}/members")->json() ?? [];
        } catch (\Exception $e) {
            return ['data' => ['members' => []]];
        }
    }

    // 8. Tarik Phonebook
    public function getPhonebook(string $sessionId)
    {
        try {
            return $this->client()->get("/api/contacts/phonebook/{$sessionId}")->json() ?? [];
        } catch (\Exception $e) {
            return ['data' => []];
        }
    }

    // 9. Dispatch Broadcast
    public function dispatchBroadcast(string $sessionId, array $recipients, string $message, ?string $mediaUrl = null)
    {
        try {
            return $this->client()->post('/api/broadcast/dispatch', [
                'sessionId' => $sessionId,
                'recipients' => $recipients,
                'message' => $message,
                'mediaUrl' => $mediaUrl,
            ])->json() ?? [];
        } catch (\Exception $e) {
            return ['status' => 'error'];
        }
    }
}
