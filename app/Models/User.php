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
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable, HasRoles;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

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

    protected static function booted(): void
    {
        // Otomatis buatkan kantor default jika user baru dibuat dan belum memiliki kantor
        static::created(function (User $user) {
            if ($user->offices()->count() === 0) {
                $fullName = $user->name ?: 'Utama';
                $officeName = 'Kantor ' . Str::headline($fullName);
                $uniqueSlug = Str::slug($officeName) . '-' . Str::lower(Str::random(4));

                while (Office::where('slug', $uniqueSlug)->exists()) {
                    $uniqueSlug = Str::slug($officeName) . '-' . Str::lower(Str::random(4));
                }

                $office = Office::create([
                    'name'                => $officeName,
                    'slug'                => $uniqueSlug,
                    'is_active'           => true,
                    'subscription_status' => 'free',
                    'expired_at'          => null,
                ]);

                $user->offices()->attach($office->id);

                if (method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole('admin');
                    } catch (\Throwable $e) {}
                }
            }
        });
    }
}
