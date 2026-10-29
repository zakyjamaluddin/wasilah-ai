<?php
namespace App\Models;

use App\Services\BaileysService;
use App\Services\MetaGraphService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Channel extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'credentials' => 'array',
        'bot_schedule' => 'array',
        'is_bot_enabled' => 'boolean',
        'work_days' => 'array',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }

    public function getBotScheduleTypeAttribute($value): string
    {
        return $value 
            ?? $this->bot_schedule['type'] 
            ?? $this->bot_schedule['bot_schedule_type'] 
            ?? 'always';
    }

    public function getOfficeStartTimeAttribute($value): string
    {
        return $value 
            ?? $this->bot_schedule['start_time'] 
            ?? $this->bot_schedule['office_start_time'] 
            ?? '08:00';
    }

    public function getOfficeEndTimeAttribute($value): string
    {
        return $value 
            ?? $this->bot_schedule['end_time'] 
            ?? $this->bot_schedule['office_end_time'] 
            ?? '17:00';
    }

    public function getWorkDaysAttribute($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) return $decoded;
        }
        return $this->bot_schedule['work_days'] ?? ['1', '2', '3', '4', '5', '6'];
    }

    /**
     * Memeriksa apakah AI Bot saat ini aktif untuk membalas pesan sesuai jadwal.
     */
    public function shouldBotReplyNow(): bool
    {
        // 1. Jika saklar master bot nonaktif
        if (!$this->is_bot_enabled) {
            return false;
        }

        // 2. Jika mode 24 jam nonstop
        $scheduleType = $this->bot_schedule_type ?? 'always';
        if ($scheduleType === 'always') {
            return true;
        }

        // 3. Mode 'outside_office_hours' (Hanya Aktif di Luar Jam Kerja CS)
        if ($scheduleType === 'outside_office_hours') {
            $now = now('Asia/Jakarta');

            // Cek hari kerja (default Senin - Sabtu: 1 - 6)
            $workDays = $this->work_days ?: ['1', '2', '3', '4', '5', '6'];
            $currentDayIso = (string) $now->dayOfWeekIso; // 1 (Senin) s/d 7 (Minggu)

            if (!in_array($currentDayIso, array_map('strval', $workDays))) {
                // Hari ini hari libur CS, maka bot AKTIF
                return true;
            }

            $startTime = trim($this->office_start_time ?: '08:00');
            $endTime = trim($this->office_end_time ?: '17:00');

            $startFormatted = date('H:i', strtotime($startTime));
            $endFormatted = date('H:i', strtotime($endTime));
            $currentTime = $now->format('H:i');

            // Tentukan apakah saat ini berada di dalam jam kerja CS
            if ($startFormatted <= $endFormatted) {
                // Jam kerja normal (cth: 08:00 s/d 17:00)
                $isOfficeHours = ($currentTime >= $startFormatted && $currentTime < $endFormatted);
            } else {
                // Jam kerja lintas tengah malam (cth: 22:00 s/d 06:00)
                $isOfficeHours = ($currentTime >= $startFormatted || $currentTime < $endFormatted);
            }

            // Jika sekarang jam kerja CS, bot TIDAK aktif (human CS yang melayani).
            // Jika di luar jam kerja CS, bot AKTIF membalas otomatis.
            return !$isOfficeHours;
        }

        return true;
    }

    // 🔥 OTOMATIS BERSIHKAN SESI BAILEYS SAAT RECORD DIHAPUS
    protected static function booted()
    {
        // Sinkronisasi data schedule ke JSON bot_schedule saat saving
        static::saving(function (Channel $channel) {
            $schedule = $channel->bot_schedule ?? [];
            $schedule['type'] = $channel->bot_schedule_type ?? 'always';
            $schedule['start_time'] = $channel->office_start_time ?? '08:00';
            $schedule['end_time'] = $channel->office_end_time ?? '17:00';
            $schedule['work_days'] = $channel->work_days ?? ['1', '2', '3', '4', '5', '6'];
            $channel->bot_schedule = $schedule;
        });
        // 1. OTOMATIS BERSIHKAN SESI BAILEYS SAAT RECORD WA DIHAPUS
        static::deleted(function (Channel $channel) {
            if ($channel->type === 'whatsapp' && !empty($channel->identifier)) {
                try {
                    $baileys = app(BaileysService::class);
                    $baileys->logoutSession($channel->identifier);
                    Log::info("[Baileys Cleanup] Sesi {$channel->identifier} berhasil dihapus dari VPS.");
                } catch (\Exception $e) {
                    Log::error("[Baileys Cleanup Error] Gagal menghapus sesi: " . $e->getMessage());
                }
            }
        });

        // 2. 🔥 OTOMATIS DAFTARKAN WEBHOOK META SETIAP CHANNEL FB/IG DIBUAT ATAU DI-UPDATE
        static::saved(function (Channel $channel) {
            if (in_array($channel->type, ['facebook', 'instagram']) && !empty($channel->identifier)) {
                $token = $channel->credentials['access_token'] ?? null;

                if ($token) {
                    try {
                        $meta = app(MetaGraphService::class);
                        $res = $meta->subscribePageWebhook($channel->identifier, $token);
                        $isConnected = !empty($res['success']) && $res['success'] === true;

                        // Update status tanpa memicu loop event
                        $channel->withoutEvents(function () use ($channel, $isConnected) {
                            $channel->update([
                                'status' => $isConnected ? 'connected' : 'disconnected'
                            ]);
                        });

                        Log::info("[Meta Auto-Connect] Halaman {$channel->name} ({$channel->identifier}) status: " . ($isConnected ? 'CONNECTED' : 'FAILED'));
                    } catch (\Exception $e) {
                        Log::error("[Meta Auto-Connect Error] " . $e->getMessage());
                    }
                }
            }
        });
    }
}
