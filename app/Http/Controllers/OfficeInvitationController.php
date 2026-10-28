<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OfficeInvitationController extends Controller
{
    /**
     * Tampilkan halaman penerimaan undangan kantor
     */
    public function show(Request $request, string $code)
    {
        $office = Office::where('invitation_code', $code)->where('is_active', true)->first();

        if (!$office) {
            abort(404, 'Tautan undangan tidak valid, telah kedaluwarsa, atau kantor sedang non-aktif.');
        }

        // Jika user sudah dalam keadaan login:
        if (Auth::check()) {
            $user = Auth::user();

            // Cek apakah sudah terdaftar di kantor ini
            $alreadyJoined = $user->offices()->where('office_id', $office->id)->exists();

            if ($alreadyJoined) {
                return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $office->slug])
                    ->with('info', 'Anda sudah terdaftar sebagai pengelola di ' . $office->name);
            }

            // Jika belum terdaftar, langsung tambahkan sebagai pengelola (Admin)
            $user->offices()->syncWithoutDetaching([$office->id]);
            setPermissionsTeamId($office->id);
            if (method_exists($user, 'assignRole')) {
                try {
                    $user->assignRole('admin');
                } catch (\Throwable $e) {
                    Log::warning('Gagal assign role admin saat accept invite (auth): ' . $e->getMessage());
                }
            }

            return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $office->slug])
                ->with('success', 'Selamat! Anda berhasil bergabung menjadi pengelola di ' . $office->name);
        }

        // Jika user belum login / belum punya akun, simpan kode undangan di session dan tampilkan landing invite
        session(['pending_office_invite' => $office->invitation_code]);

        return view('auth.office-invite', compact('office'));
    }
}
