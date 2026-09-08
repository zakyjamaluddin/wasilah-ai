<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Kantor Utama Perdana jika belum ada
        $office = Office::firstOrCreate(
            ['slug' => 'wasilah-pusat'],
            [
                'name' => 'Wasilah Kantor Pusat',
                'phone' => '081234567890',
                'address' => 'Kantor Pusat Wasilah AI',
                'is_active' => true,
            ]
        );

        // 2. Set Context Spatie Teams ke Kantor Ini
        setPermissionsTeamId($office->id);

        // 3. Buat Role-Role Standar
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_cabang', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff_cs', 'guard_name' => 'web']);

        // 4. Buat Akun Super Admin Perdana (Developer)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@wasilah.com'],
            [
                'name' => 'Super Admin Developer',
                'password' => Hash::make('password123'), // Ganti password ini sesuai keinginan Anda
                'email_verified_at' => now(),
            ]
        );

        // 5. Hubungkan Super Admin ke Kantor Pusat
        if (!$superAdmin->offices()->where('offices.id', $office->id)->exists()) {
            $superAdmin->offices()->attach($office->id);
        }

        // 6. Tempelkan Role super_admin
        $superAdmin->assignRole($superAdminRole);

        $this->command->info("=================================================");
        $this->command->info("🎉 WASILAH INITIAL SETUP BERHASIL!");
        $this->command->info("📧 Email Login    : admin@wasilah.com");
        $this->command->info("🔑 Password       : password123");
        $this->command->info("🏢 Kantor Aktif   : Wasilah Kantor Pusat (wasilah-pusat)");
        $this->command->info("👑 Peran (Role)   : super_admin (God Mode)");
        $this->command->info("=================================================");
    }
}