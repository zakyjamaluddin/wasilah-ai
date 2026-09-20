<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Office extends Model implements HasName
{
    use HasFactory;

    protected $guarded = ['id'];

    // Otomatis buat slug saat nama kantor diisi
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($office) {
            if (empty($office->slug)) {
                $office->slug = Str::slug($office->name);
            }
        });
    }

     protected $casts = [
        'is_active'   => 'boolean',
        'expired_at'  => 'datetime',
    ];

    // Nama kantor yang ditampilkan di UI Filament
    public function getFilamentName(): string
    {
        return $this->name;
    }

    // Relasi ke User
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'office_user');
    }

    // Relasi ke Channels (WA, FB, IG)
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    // Relasi ke Kontak Leads
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    // Relasi ke Percakapan Omnichannel
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    // Relasi ke Knowledge Base
    public function knowledgeBases(): HasMany
    {
        return $this->hasMany(KnowledgeBase::class);
    }

    // Relasi ke Broadcast Campaign
    public function broadcastCampaigns(): HasMany
    {
        return $this->hasMany(BroadcastCampaign::class);
    }


    /**
     * Relasi: Setiap kantor terhubung ke 1 akun Composio.
     */
    public function composioAccount(): BelongsTo
    {
        return $this->belongsTo(ComposioAccount::class, 'composio_account_id');
    }



    /**
     * Ambil status langganan dinamis (otomatis mendeteksi H-7 & expired).
     */
    public function getEffectiveSubscriptionStatusAttribute(): string
    {
        // 1. Jika memang diatur 'free', tetap 'free'
        if ($this->subscription_status === 'free' || empty($this->expired_at)) {
            return 'free';
        }

        // 2. Jika tanggal sudah lewat dari hari ini -> 'inactive'
        if ($this->expired_at->isPast()) {
            return 'inactive';
        }

        // 3. Jika tersisa <= 7 hari sebelum expired -> 'expiring'
        if ((now()->diffInDays($this->expired_at, false) + 1) <= 7) {
            return 'expiring';
        }

        // 4. Masih aktif normal
        return 'active';
    }

    /**
     * Cek apakah tenant memiliki hak akses aktif (bisa active atau expiring).
     */
    public function hasActiveSubscription(): bool
    {
        $status = $this->effective_subscription_status;
        return in_array($status, ['active', 'expiring']);
    }

    /**
     * Cek apakah masa aktif dalam masa tenggang peringatan (H-7).
     */
    public function isExpiringSoon(): bool
    {
        return $this->effective_subscription_status === 'expiring';
    }

    /**
     * Cek apakah masa aktif sudah habis / belum bayar.
     */
    public function isInactive(): bool
    {
        return in_array($this->effective_subscription_status, ['free', 'inactive']);
    }

    /**
     * Hitung sisa hari masa aktif langganan.
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->expired_at) {
            return null;
        }

        if ($this->expired_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expired_at, false) + 1;
    }

}
