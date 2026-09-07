<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 👑 SUPER ADMIN GOD MODE BYPASS
        Gate::before(function ($user, $ability) {
            return ($user->id === 1 || $user->hasRole('super_admin')) ? true : null;
        });

        // 🔥 2. OTOMATIS SINKRONKAN OFFICE_ID DENGAN SPATIE PERMISSIONS
        // Filament::serving(function () {
        //     if ($tenant = Filament::getTenant()) {
        //         setPermissionsTeamId($tenant->id);
        //     }
        // });

        if (config('app.env') == 'production') {
        URL::forceScheme('https');

        app(PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);




        }
    }
}
