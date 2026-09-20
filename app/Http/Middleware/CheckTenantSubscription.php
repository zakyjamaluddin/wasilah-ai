<?php

namespace App\Http\Middleware;

use App\Models\Office;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    /**
     * Tangani request dan verifikasi masa aktif subscription kantor.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Super Admin bypass semua proteksi
        if ($user && ($user->id === 1 || $user->hasRole('super_admin'))) {
            return $next($request);
        }

        // 2. Dapatkan tenant/kantor yang sedang aktif
        $currentOffice = Filament::getTenant();

        // Jika rute bukan milik tenant (misal: landing page / checkout), loloskan
        if (!$currentOffice instanceof Office) {
            return $next($request);
        }

        // 3. Jika status kantor aktif (active atau expiring), loloskan
        if ($currentOffice->hasActiveSubscription()) {
            return $next($request);
        }

        // 4. DAFTAR RUTE YANG TETAP BOLEH DIAKSES SAAT INACTIVE / FREE:
        // - Dashboard kantor
        // - Halaman Kelola Subscription
        // - Logout & Edit Profil
        $routeName = $request->route()?->getName() ?? '';

        $allowedRoutePatterns = [
            'filament.admin.pages.dashboard',
            'filament.admin.tenant',
            'filament.admin.pages.subscription',
            'filament.admin.auth.logout',
        ];

        foreach ($allowedRoutePatterns as $pattern) {
            if (str_starts_with($routeName, $pattern) || $routeName === $pattern) {
                return $next($request);
            }
        }

        // 5. JIKA MENCOBA AKSES RUTE OPERASIONAL (CHANNELS, CRM, BROADCAST, DLL): CEGAT!
        Notification::make()
            ->title('🔒 Akses Fitur Terkunci')
            ->body("Masa aktif kantor <b>{$currentOffice->name}</b> belum aktif atau telah berakhir. Silakan lakukan aktivasi/perpanjangan langganan.")
            ->danger()
            ->persistent()
            ->send();

        // Alihkan ke Dashboard / Halaman Subscription kantor terkait
        return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $currentOffice->slug]);
    }
}
