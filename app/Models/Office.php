<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
