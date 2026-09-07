<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected string $apiKey;
    protected string $model;


    public function __construct()
    {
        // 🛡️ BACA API KEY & MODEL DENGAN FALLBACK GANDA
        $this->apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY', '');
        $this->model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash');
    }

    /**
     * 1. Menghasilkan Balasan Chatbot Otomatis (Teks + AI Vision Gambar)
     */
    public function generateReply(Conversation $conversation, ?string $imageLocalPath = null): ?string
    {
        $office = $conversation->office;
        $contact = $conversation->contact;

        // A. Kumpulkan Materi Knowledge Base Kantor untuk Platform Ini
        $knowledge = KnowledgeBase::where('office_id', $office->id)
            ->where('is_active', true)
            ->whereIn('platform', ['all', $conversation->channel_type])
            ->pluck('content')
            ->filter()
            ->implode("\n\n---\n\n");

        if (empty($knowledge)) {
            $knowledge = "Informasi umum: Layanan customer service kantor {$office->name}.";
        }

        // B. Kumpulkan 6 Percakapan Terakhir
        $recentMessages = $conversation->messages()
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->reverse();

        $chatHistoryText = "";
        foreach ($recentMessages as $msg) {
            $role = $msg->sender_type === 'customer' ? 'Customer' : 'Sales/Bot';
            $chatHistoryText .= "{$role}: {$msg->message_body}\n";
        }

        // C. Susun Instruksi Prompt Utama
        $systemInstruction = "Anda adalah Customer Service & Sales Representative profesional dari '{$office->name}'.
Tugas Anda:
1. Jawab pertanyaan customer secara ramah, ringkas, jelas, dan persuasif dalam Bahasa Indonesia.
2. Gunakan DATA PENGETAHUAN di bawah ini sebagai sumber kebenaran mutlak. JANGAN mengarang jawaban jika tidak ada di data pengetahuan. Jika tidak tahu, arahkan untuk menunggu admin manusia.
3. Nama customer saat ini: '{$contact->name}'.
4. Jika customer mengirim gambar, analisis gambar tersebut dan hubungkan dengan layanan bisnis kita.

DATA PENGETAHUAN BISNIS KITA:
{$knowledge}
";

        // D. Siapkan Payload Part (Teks & Gambar Vision jika ada)
        $userParts = [];

        // Masukkan Gambar jika ada file fisik lokal
        if ($imageLocalPath && file_exists($imageLocalPath)) {
            $mimeType = mime_content_type($imageLocalPath) ?: 'image/jpeg';
            $imageData = base64_encode(file_get_contents($imageLocalPath));
            $userParts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageData,
                ]
            ];
        }

        $userParts[] = [
            'text' => "RIWAYAT PERCAKAPAN TERAKHIR:\n{$chatHistoryText}\n\nBerikan balasan terbaik Anda sekarang:"
        ];

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [['text' => $systemInstruction]]
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => $userParts
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4, // Suhu rendah agar jawaban presisi & konsisten
                        'maxOutputTokens' => 500,
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
                return trim($reply);
            } else {
                Log::error("[Gemini Error] HTTP " . $response->status() . ": " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("[Gemini Exception] " . $e->getMessage());
            return null;
        }
    }

    /**
     * 2. Analisis & Ringkas Kebutuhan Leads serta Update Status Prospek (Cold / Hot)
     */
    public function analyzeAndSummarizeLead(Conversation $conversation): void
    {
        $contact = $conversation->contact;
        $messages = $conversation->messages()->orderBy('id', 'desc')->limit(10)->get()->reverse();

        $chatText = "";
        foreach ($messages as $msg) {
            $sender = $msg->sender_type === 'customer' ? 'Customer' : 'Bot';
            $chatText .= "{$sender}: {$msg->message_body}\n";
        }

        $prompt = "Analisis percakapan obrolan berikut dan berikan output dalam format JSON murni:
1. 'summary': Ringkasan singkat poin kebutuhan customer, barang yang dicari, atau kendala yang dihadapi (maksimal 2 kalimat).
2. 'stage': Tentukan status prospek dari opsi: ['cold_prospect', 'warm_prospect', 'hot_prospect', 'closing'].

PERCAKAPAN:
{$chatText}

HANYA KEMBALIKAN JSON (tanpa markdown blok ```json):
{\"summary\": \"...\", \"stage\": \"...\"}";

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
            $res = Http::timeout(15)->post($endpoint, [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($res->successful()) {
                $rawJson = $res->json('candidates.0.content.parts.0.text');
                $rawJson = str_replace(['```json', '```'], '', trim($rawJson));
                $parsed = json_decode($rawJson, true);

                if (!empty($parsed['summary'])) {
                    $contact->update([
                        'ai_summary' => $parsed['summary'],
                        'pipeline_stage' => $parsed['stage'] ?? $contact->pipeline_stage,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("[Gemini Summarizer Error] " . $e->getMessage());
        }
    }

    /**
     * 3. 🔥 KHUSUS GENERATE PESAN FOLLOW-UP CERDAS (Berdasarkan Instruksi Khusus Admin)
     */

    public function generateFollowUpMessage(Conversation $conversation, string $aiInstructionPrompt): ?string
    {
        $office = $conversation->office;
        $contact = $conversation->contact;

        // 1. Ambil Materi Knowledge Base Kantor
        $knowledge = KnowledgeBase::where('office_id', $office->id)
            ->where('is_active', true)
            ->pluck('content')
            ->filter()
            ->implode("\n\n---\n\n");

        if (empty($knowledge)) {
            $knowledge = "Layanan travel & customer service resmi {$office->name}.";
        }

        // 2. Ambil Riwayat Chat Terakhir
        $recentMessages = $conversation->messages()->orderBy('id', 'desc')->limit(6)->get()->reverse();
        $chatHistoryText = "";
        foreach ($recentMessages as $msg) {
            $role = $msg->sender_type === 'customer' ? 'Customer' : 'Sales/CS';
            $chatHistoryText .= "{$role}: {$msg->message_body}\n";
        }

        $leadSummary = $contact->ai_summary ?: "Customer bertanya rincian informasi.";

        // 3. Susun Instruksi AI
        $promptText = "Anda adalah Sales Representative profesional dari '{$office->name}'.
TUGAS: Buat 1 pesan follow-up lanjutan untuk customer bernama '{$contact->name}'.

INSTRUKSI KHUSUS DARI OWNER:
\"{$aiInstructionPrompt}\"

KONTEKS CUSTOMER:
- Nama: {$contact->name}
- Ringkasan Kebutuhan: {$leadSummary}
- Percakapan Terakhir:
{$chatHistoryText}

DATA PENGETAHUAN KANTOR:
{$knowledge}

ATURAN OUTPUT:
1. Sapa nama '{$contact->name}' secara ramah dalam Bahasa Indonesia.
2. Tulis pesan persuasif yang mengalir natural (maksimal 2-3 kalimat).
3. Langsung berikan teks pesan siap kirim ke WhatsApp tanpa tanda petik atau kalimat pembuka tambahan.";

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->post($endpoint, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $promptText]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 400,
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
                return trim($reply);
            } else {
                Log::error("[Gemini FollowUp Error] HTTP {$response->status()}: " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("[Gemini FollowUp Exception] " . $e->getMessage());
            return null;
        }
    }
    public function generateFollowUpMessageLama(Conversation $conversation, string $aiInstructionPrompt): ?string
    {
        $office = $conversation->office;
        $contact = $conversation->contact;

        // 1. Ambil Materi Knowledge Base
        $knowledge = KnowledgeBase::where('office_id', $office->id)
            ->where('is_active', true)
            ->pluck('content')
            ->filter()
            ->implode("\n\n---\n\n");

        // 2. Ambil 5 Chat Terakhir & Ringkasan Kebutuhan
        $recentMessages = $conversation->messages()->orderBy('id', 'desc')->limit(5)->get()->reverse();
        $chatHistoryText = "";
        foreach ($recentMessages as $msg) {
            $role = $msg->sender_type === 'customer' ? 'Customer' : 'Sales/CS';
            $chatHistoryText .= "{$role}: {$msg->message_body}\n";
        }

        $leadSummary = $contact->ai_summary ?: "Customer baru bertanya informasi.";

        // 3. Susun Prompt Follow-Up yang Kuat
        $systemInstruction = "Anda adalah Sales & CS profesional dari '{$office->name}'.
TUGAS ANDA: Buat 1 pesan follow-up lanjutan untuk customer bernama '{$contact->name}'.

INSTRUKSI KHUSUS DARI OWNER:
\"{$aiInstructionPrompt}\"

KONTEKS CUSTOMER:
- Ringkasan Kebutuhan: {$leadSummary}
- Obrolan Sebelumnya:
{$chatHistoryText}

DATA PENGETAHUAN PRODUK/KANTOR:
{$knowledge}

ATURAN PESAN:
1. Sapa nama customer dengan hangat (Bahasa Indonesia).
2. Tulis pesan follow-up yang singkat, persuasif, dan tidak kaku (maksimal 3-4 kalimat).
3. Langsung kembalikan teks pesan siap kirim (tanpa tanda petik atau kata pengantar tambahan).";

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->post($endpoint, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $systemInstruction]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 300,
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
                return trim($reply);
            }
            return null;
        } catch (\Exception $e) {
            Log::error("[Gemini FollowUp Error] " . $e->getMessage());
            return null;
        }
    }
}
