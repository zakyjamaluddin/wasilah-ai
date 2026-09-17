<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComposioAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'webhook_secret', // <-- Tambahkan ini
        'max_offices',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_offices' => 'integer',
    ];

    /**
     * Relasi: 1 Akun Composio bisa dipakai oleh banyak Kantor (Offices).
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class, 'composio_account_id');
    }

    /**
     * Mengecek apakah kuota kantor pada token ini masih tersisa (< 4).
     */
    public function hasAvailableSlot(): bool
    {
        return $this->offices()->count() < $this->max_offices;
    }
}