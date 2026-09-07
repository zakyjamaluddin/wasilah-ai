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
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }

    // 🔥 OTOMATIS BERSIHKAN SESI BAILEYS SAAT RECORD DIHAPUS
     protected static function booted()
    {
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
