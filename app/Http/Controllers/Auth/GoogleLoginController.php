<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * Redirect pengguna ke halaman otentikasi Google
     */
    public function redirect(Request $request)
    {
        // Jika ada parameter code dari invitation url, simpan ke session
        if ($request->has('invite')) {
            session(['pending_office_invite' => $request->query('invite')]);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangkap callback otentikasi dari Google
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect('/admin/login')->withErrors(['email' => 'Gagal masuk dengan Google. Silakan coba lagi.']);
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $name = $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Pengguna Google');

        // Cari atau buat User baru
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'password'          => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'avatar_url'        => $googleUser->getAvatar(),
            ]);
        } else {
            $user->update([
                'avatar_url' => $googleUser->getAvatar() ?: $user->avatar_url,
            ]);
        }

        // Cek apakah ada sesi undangan kantor (Invitation Link)
        $invitationCode = session('pending_office_invite');
        $invitedOffice = null;

        if ($invitationCode) {
            $invitedOffice = Office::where('invitation_code', $invitationCode)
                ->where('is_active', true)
                ->first();
        }

        if ($invitedOffice) {
            // 1. Hubungkan User ke Kantor yang mengundang
            $user->offices()->syncWithoutDetaching([$invitedOffice->id]);

            // 2. Beri peran pengelola (Role: admin) untuk kantor ini
            setPermissionsTeamId($invitedOffice->id);
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('admin');
                } catch (\Throwable $e) {
                    Log::warning('Gagal assign role admin saat accept invite: ' . $e->getMessage());
                }
            }

            // Hapus sesi undangan agar tidak terpanggil ulang
            session()->forget('pending_office_invite');

            Auth::login($user, true);

            return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $invitedOffice->slug])
                ->with('success', 'Selamat datang! Anda resmi bergabung sebagai pengelola di ' . $invitedOffice->name);
        }

        // Login normal tanpa undangan
        Auth::login($user, true);

        // Jika user memiliki kantor, arahkan ke kantor pertamanya
        if ($user->offices()->count() > 0) {
            $firstOffice = $user->offices()->first();
            return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $firstOffice->slug]);
        }

        return redirect()->to('/admin');
    }
}
