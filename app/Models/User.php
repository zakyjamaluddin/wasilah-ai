<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable, HasRoles;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 1. Relasi ke Banyak Kantor (Tenants)
    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'office_user');
    }

    // 2. Kontrak Filament: Ambil daftar kantor yang bisa diakses user ini
    public function getTenants(Panel $panel): Collection
    {
        return $this->offices;
    }

    // 3. Kontrak Filament: Cek apakah user punya hak akses ke kantor tertentu
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->offices()->whereKey($tenant)->exists();
    }

    // 4. Hak akses masuk panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
