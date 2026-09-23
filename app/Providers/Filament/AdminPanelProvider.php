<?php

namespace App\Providers\Filament;

use App\Http\Middleware\CheckTenantSubscription;
use App\Models\Office;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Matondojk\FilamentSocialLogin\FilamentSocialLoginPlugin;
use Matondo\FilamentSocialLogin\Provider;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->globalSearch(false)
            // 🔥 AKTIFKAN MULTI-TENANCY KANTOR
            ->tenant(Office::class, slugAttribute: 'slug')
            ->tenantMiddleware([
                CheckTenantSubscription::class, // 👈 Pasang satpam penjaga tenant di sini
            ], isPersistent: true)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\GreetingOverviewWidget::class,
                \App\Filament\Widgets\CrmStatsOverviewWidget::class,
                \App\Filament\Widgets\HotLeadsTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentSocialLoginPlugin::make()
                    // ->providers([
                    //     Provider::make('google')
                    //         ->label('Masuk dengan Google')
                    //         ->icon('heroicon-m-globe-alt')
                    //         ->color('danger'),
                    // ])
                    // // 🔥 LOGIKA OTOMATIS SAAT USER BARU LOGIN GOOGLE
                    // ->createUserUsing(function (string $provider, SocialiteUser $oauthUser) {
                    //     return DB::transaction(function () use ($oauthUser) {
                    //         $email = strtolower(trim($oauthUser->getEmail()));
                    //         $fullName = $oauthUser->getName() ?? $oauthUser->getNickname() ?? 'Pengguna Google';

                    //         // 1. Cari atau buat data User di tabel `users`
                    //         $user = User::firstOrCreate(
                    //             ['email' => $email],
                    //             [
                    //                 'name'              => $fullName,
                    //                 'password'          => Hash::make(Str::random(32)), // Random hash aman
                    //                 'email_verified_at' => now(),
                    //             ]
                    //         );

                    //         // 2. Cek apakah user sudah memiliki kantor atau belum
                    //         if ($user->offices()->count() === 0) {
                    //             // Generate nama & slug kantor yang unik
                    //             $officeName = 'Kantor ' . Str::headline($fullName);
                    //             $baseSlug = Str::slug($officeName);
                    //             $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(4));

                    //             // Pastikan slug benar-benar unik di database
                    //             while (Office::where('slug', $uniqueSlug)->exists()) {
                    //                 $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(4));
                    //             }

                    //             // 3. Buat Kantor Default Baru (Status: Free)
                    //             $office = Office::create([
                    //                 'name'                => $officeName,
                    //                 'slug'                => $uniqueSlug,
                    //                 'is_active'           => true,
                    //                 'subscription_status' => 'free', // Menggunakan sistem subscription tenant kita
                    //                 'expired_at'          => null,
                    //             ]);

                    //             // 4. Hubungkan User ke Kantor di tabel pivot `office_user`
                    //             $user->offices()->attach($office->id);

                    //             // 5. Pasang Role 'admin' pemilik kantor (Spatie Permission)
                    //             if (method_exists($user, 'assignRole')) {
                    //                 try {
                    //                     $user->assignRole('admin');
                    //                 } catch (\Throwable $e) {
                    //                     // Abaikan jika role belum dimigrasi
                    //                 }
                    //             }
                    //         }

                    //         return $user;
                    //     });
                    // }),
            ])
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('filament.components.subscription-banner')
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
