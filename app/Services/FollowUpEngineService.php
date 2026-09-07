<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FollowUpEnrollment;
use App\Models\FollowUpSequence;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FollowUpEngineService
{
    /**
     * 1. Daftarkan Kontak ke Sequence Follow-Up
     */
    public function enroll(Contact $contact, FollowUpSequence $sequence, ?Conversation $conversation = null): ?FollowUpEnrollment
    {
        if (!$sequence->is_active || $sequence->steps->isEmpty()) return null;

        $firstStep = $sequence->steps->first();
        $nextScheduled = $this->calculateNextSchedule($firstStep->delay_value, $firstStep->delay_unit);

        // Hapus antrean aktif lama dari sequence yang sama jika ada
        FollowUpEnrollment::where('contact_id', $contact->id)
            ->where('follow_up_sequence_id', $sequence->id)
            ->where('status', 'active')
            ->delete();

        return FollowUpEnrollment::create([
            'office_id' => $contact->office_id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation?->id,
            'follow_up_sequence_id' => $sequence->id,
            'current_step_order' => $firstStep->step_order,
            'next_scheduled_at' => $nextScheduled,
            'status' => 'active',
        ]);
    }

    /**
     * 2. Jeda Antrean jika Customer Membalas Chat (Stop on Reply)
     */
    public function pauseOnReply(Contact $contact): void
    {
        $enrollments = FollowUpEnrollment::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->with('sequence')
            ->get();

        foreach ($enrollments as $enr) {
            if ($enr->sequence->stop_on_reply) {
                $enr->update(['status' => 'paused']);
                Log::info("[Follow-Up] Sequence {$enr->sequence->name} dijeda untuk kontak {$contact->name} karena customer membalas.");
            }
        }
    }

    /**
     * 3. Batalkan Total Antrean jika Status Jadi Won / Closing
     */
    public function cancelOnClosing(Contact $contact): void
    {
        FollowUpEnrollment::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    /**
     * 4. Mesin Eksekusi Pengiriman Follow-Up yang Jatuh Tempo (Dijalankan per Menit oleh Cron)
     */
    /**
     * 4. Mesin Eksekusi Pengiriman Follow-Up yang Jatuh Tempo
     */
    public function processDueEnrollments(): void
    {
        $now = Carbon::now();

        $dueEnrollments = FollowUpEnrollment::where('status', 'active')
            ->where('next_scheduled_at', '<=', $now)
            ->with(['contact', 'sequence.steps', 'conversation.channel'])
            ->get();

        foreach ($dueEnrollments as $enr) {
            $seq = $enr->sequence;
            if (!$seq || !$seq->is_active) continue;

            if ($seq->only_work_hours && ($now->hour < 8 || $now->hour >= 20)) {
                continue;
            }

            $currentStep = $seq->steps->where('step_order', $enr->current_step_order)->first();
            if (!$currentStep) {
                $enr->update(['status' => 'completed', 'completed_at' => now()]);
                continue;
            }

            $contact = $enr->contact;
            $conv = $enr->conversation;
            if (!$contact || !$conv) continue;

            // 🎯 1. SUSUN TEKS PESAN DENGAN AI GEMINI ATAU TEMPLATE
            $messageText = '';
            if ($currentStep->content_type === 'ai_prompt' && !empty($currentStep->ai_prompt)) {
                $gemini = app(GeminiAiService::class);
                // 🔥 Panggil fungsi AI Follow-Up Khusus dengan membawa instruksi admin
                $messageText = $gemini->generateFollowUpMessage($conv, $currentStep->ai_prompt);
            }

            if (empty($messageText)) {
                $messageText = str_replace('{{name}}', $contact->name, $currentStep->message_template ?? "Halo {$contact->name}");
            }

            // 🎯 2. EKSEKUSI PENGIRIMAN KE CHANNEL TARGET (WA, FB DM, IG DM)
            $this->sendMessage($conv, $messageText, $currentStep->media_url);

            // 🎯 3. CARI LANGKAH BERIKUTNYA
            $nextStep = $seq->steps->where('step_order', '>', $currentStep->step_order)->first();

            if ($nextStep) {
                $nextScheduled = $this->calculateNextSchedule($nextStep->delay_value, $nextStep->delay_unit);
                $enr->update([
                    'current_step_order' => $nextStep->step_order,
                    'next_scheduled_at' => $nextScheduled,
                ]);
                Log::info("[Follow-Up] {$contact->name} naik ke Step {$nextStep->step_order} ({$nextScheduled})");
            } else {
                $enr->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                Log::info("[Follow-Up] Sequence {$seq->name} SELESAI PENUH untuk {$contact->name}!");
            }
        }
    }

    /**
     * Kirim Pesan ke WhatsApp, Facebook Messenger, atau Instagram DM
     */
    protected function sendMessage(Conversation $conv, string $text, ?string $mediaPath = null): void
    {
        $mediaUrl = null;
        $messageType = 'text';

        if ($mediaPath && Storage::disk('public')->exists($mediaPath)) {
            $mediaUrl = Storage::url($mediaPath);
            $mime = Storage::disk('public')->mimeType($mediaPath);
            if (str_contains($mime, 'video')) $messageType = 'video';
            elseif (str_contains($mime, 'pdf')) $messageType = 'document';
            elseif (str_contains($mime, 'image')) $messageType = 'image';
        }

        // 1. Simpan Pesan ke Database CRM
        Message::create([
            'conversation_id' => $conv->id,
            'office_id' => $conv->office_id,
            'sender_type' => 'bot',
            'message_type' => $messageType,
            'message_body' => $text,
            'media_url' => $mediaUrl,
            'is_read' => true,
        ]);

        $conv->update(['last_message_at' => now()]);

        $channel = $conv->channel;
        if (!$channel) return;

        $accessToken = $channel->credentials['access_token'] ?? '';
        $meta = app(MetaGraphService::class);
        $baileys = app(BaileysService::class);

        // 🎯 2. EKSEKUSI PENGIRIMAN SESUAI PLATFORM MASING-MASING

        // A. Jika WhatsApp
        if ($conv->channel_type === 'whatsapp') {
            try {
                $target = $conv->contact->wa_jid ?: $conv->contact->phone_number;
                if ($mediaPath && Storage::disk('public')->exists($mediaPath)) {
                    $mediaBase64 = base64_encode(Storage::disk('public')->get($mediaPath));
                    $mime = Storage::disk('public')->mimeType($mediaPath);
                    $baileys->sendMediaMessage($channel->identifier, $target, $mediaBase64, $text, $mime);
                } else {
                    $baileys->sendTextMessage($channel->identifier, $target, $text);
                }
            } catch (\Exception $e) {
                Log::error("[FollowUp WA Error] " . $e->getMessage());
            }
        }

        // B. Jika Facebook Messenger DM
        elseif ($conv->channel_type === 'fb_dm' && !empty($accessToken)) {
            try {
                $recipientPsid = $conv->contact->fb_user_id;
                if ($recipientPsid) {
                    $res = $meta->sendFacebookMessengerReply($accessToken, $recipientPsid, $text);
                    Log::info("[FollowUp FB DM Respon] " . json_encode($res));
                }
            } catch (\Exception $e) {
                Log::error("[FollowUp FB DM Error] " . $e->getMessage());
            }
        }

        // C. Jika Instagram DM
        elseif ($conv->channel_type === 'ig_dm' && !empty($accessToken)) {
            try {
                $recipientIgid = $conv->contact->fb_user_id;
                if ($recipientIgid) {
                    $res = $meta->sendInstagramDmReply($accessToken, $recipientIgid, $text);
                    Log::info("[FollowUp IG DM Respon] " . json_encode($res));
                }
            } catch (\Exception $e) {
                Log::error("[FollowUp IG DM Error] " . $e->getMessage());
            }
        }
    }

    protected function calculateNextSchedule(int $value, string $unit): Carbon
    {
        $now = Carbon::now();
        return match ($unit) {
            'minutes' => $now->addMinutes($value),
            'hours' => $now->addHours($value),
            'weeks' => $now->addWeeks($value),
            default => $now->addDays($value),
        };
    }
}
