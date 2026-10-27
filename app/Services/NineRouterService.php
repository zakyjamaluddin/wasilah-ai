<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NineRouterService
{
    public const MODEL_FREE = 'chat-free';
    public const MODEL_FAST = 'chat-fast';
    public const MODEL_VISION = 'chat-vision';

    protected string $baseUrl;
    protected string $apiKey;
    protected string $modelFree;
    protected string $modelFast;
    protected string $modelVision;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.nine_router.base_url') ?: env('NINEROUTER_BASE_URL', 'https://9router.zakyapps.my.id/v1'), '/');
        $this->apiKey = config('services.nine_router.key') ?: env('NINEROUTER_API_KEY', '');
        $this->modelFree = config('services.nine_router.model_free') ?: env('NINEROUTER_MODEL_FREE', self::MODEL_FREE);
        $this->modelFast = config('services.nine_router.model_fast') ?: env('NINEROUTER_MODEL_FAST', self::MODEL_FAST);
        $this->modelVision = config('services.nine_router.model_vision') ?: env('NINEROUTER_MODEL_VISION', self::MODEL_VISION);
        $this->timeout = (int) (config('services.nine_router.timeout') ?: env('NINEROUTER_TIMEOUT', 30));
    }

    /**
     * Kirim permintaan Chat Completion ke 9Router (OpenAI-compatible endpoint).
     *
     * @param array $messages Array pesan OpenAI [{role: 'system'|'user'|'assistant', content: string|array}]
     * @param string $model Model 9Router (chat-free, chat-fast, chat-vision)
     * @param array $options Opsi tambahan seperti temperature, max_tokens
     * @return string|null
     */
    public function chatCompletion(array $messages, string $model = self::MODEL_FREE, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::error("[9Router] API Key tidak ditemukan. Pastikan NINEROUTER_API_KEY telah diatur.");
            return null;
        }

        $endpoint = "{$this->baseUrl}/chat/completions";

        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
        ], $options);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($endpoint, $payload);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if ($content !== null) {
                    return trim($content);
                }
                Log::warning("[9Router] Response sukses namun content kosong: " . $response->body());
                return null;
            }

            Log::error("[9Router Error] HTTP {$response->status()}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("[9Router Exception] " . $e->getMessage());
            return null;
        }
    }

    /**
     * 1. Menghasilkan Balasan Chatbot Otomatis (Teks via chat-fast/chat-free + Vision via chat-vision).
     */
    public function generateReply(Conversation $conversation, ?string $imageLocalPath = null, ?string $modelOverride = null): ?string
    {
        $office = $conversation->office;
        $contact = $conversation->contact;

        $rawChannelType = strtolower($conversation->channel_type ?? '');

        $targetPlatform = match ($rawChannelType) {
            'whatsapp'                          => 'whatsapp',
            'facebook', 'fb_dm', 'fb_comment'  => 'facebook',
            'instagram', 'ig_dm', 'ig_comment' => 'instagram',
            default                             => 'whatsapp',
        };

        // A. Kumpulkan Materi Knowledge Base Kantor untuk Platform Ini
        $knowledge = KnowledgeBase::where('office_id', $office->id)
            ->where('is_active', true)
            ->whereIn('platform', ['all', $targetPlatform])
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
2. LANGSUNG jawab inti pertanyaan customer secara ringkas, to the point, ramah, dan mengalir seperti manusia (maksimal 2-3 kalimat).
3. JANGAN PERNAH mengulang sapaan pembuka seperti 'Halo [Nama]' atau 'Terima kasih telah menghubungi kami' di setiap balasan jika obrolan sudah berjalan! Ini obrolan chat langsung, bukan surat formal.
4. Gunakan DATA PENGETAHUAN di bawah ini sebagai sumber kebenaran mutlak. JANGAN mengarang jawaban jika tidak ada di data pengetahuan. Jika tidak tahu, arahkan untuk menunggu admin manusia.
5. Nama customer saat ini: '{$contact->name}' (tidak perlu menuliskan nama di setiap percakapan. gunakan variabel nama ini sesederhana mungkin (karena berpotensi salah) agar tidak membuat customer bingung/risih).
6. Hanya gunakan sapaan pembuka di awal JIKA customer baru pertama kali menyapa dengan salam pembuka (misal: 'Halo', 'Pagi', 'Assalamu'alaikum').
7. Jika customer mengirim gambar, analisis gambar tersebut dan hubungkan dengan layanan bisnis kita.
8. Akhiri jawaban dengan pertanyaan/sekedar tanya konfirmasi persuasif ala sales yang bisa memicu menggali kebutuhan customer

DATA PENGETAHUAN BISNIS KITA:
{$knowledge}
";

        // D. Siapkan Pesan (Teks & Gambar Vision jika ada)
        $hasImage = $imageLocalPath && file_exists($imageLocalPath);

        // Model selection:
        // - Jika ada gambar: pilih model vision (chat-vision)
        // - Jika teks murni: pilih model chat tercepat (chat-fast)
        $selectedModel = $modelOverride ?: ($hasImage ? $this->modelVision : $this->modelFast);

        $messages = [
            [
                'role' => 'system',
                'content' => $systemInstruction,
            ],
        ];

        $promptText = "RIWAYAT PERCAKAPAN TERAKHIR:\n{$chatHistoryText}\n\nBerikan balasan terbaik Anda sekarang:";

        if ($hasImage) {
            $mimeType = mime_content_type($imageLocalPath) ?: 'image/jpeg';
            $imageData = base64_encode(file_get_contents($imageLocalPath));
            $dataUri = "data:{$mimeType};base64,{$imageData}";

            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $promptText,
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $dataUri,
                        ],
                    ],
                ],
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $promptText,
            ];
        }

        $reply = $this->chatCompletion($messages, $selectedModel, [
            'temperature' => 0.4,
            'max_tokens' => 500,
        ]);

        // Fallback jika mode gambar pada chat-vision gagal, coba model serbaguna chat-free
        if ($reply === null && $hasImage && $selectedModel !== $this->modelFree) {
            Log::warning("[9Router] Percobaan fallback ke model '{$this->modelFree}' untuk pesan bergambar...");
            $reply = $this->chatCompletion($messages, $this->modelFree, [
                'temperature' => 0.4,
                'max_tokens' => 500,
            ]);
        }

        return $reply;
    }

    /**
     * 2. Analisis & Ringkas Kebutuhan Leads serta Update Status Prospek (Cold / Hot).
     * Menggunakan model chat-fast untuk kecepatan respons maksimal.
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
            $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ];

            $rawJson = $this->chatCompletion($messages, $this->modelFast, [
                'temperature' => 0.2,
                'max_tokens' => 200,
            ]);

            if ($rawJson) {
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
            Log::error("[9Router Summarizer Error] " . $e->getMessage());
        }
    }

    /**
     * 3. Generate Pesan Follow-Up Cerdas Berdasarkan Instruksi Khusus Admin.
     * Menggunakan model serbaguna chat-free untuk gaya bahasa persuasif.
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
3. Langsung berikan teks pesan siap kirim ke WhatsApp tanpa tanda petik atau kalimat pembuka tambahan.
4. Akhiri dengan pertanyaan persuasif ala sales yang bisa memancing kebutuhan calon customer";

        $messages = [
            [
                'role' => 'user',
                'content' => $promptText,
            ],
        ];

        return $this->chatCompletion($messages, $this->modelFree, [
            'temperature' => 0.7,
            'max_tokens' => 400,
        ]);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getModel(string $type = 'free'): string
    {
        return match ($type) {
            'fast' => $this->modelFast,
            'vision' => $this->modelVision,
            default => $this->modelFree,
        };
    }
}
