<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Office;
use App\Services\MetaGraphService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookOAuthController extends Controller
{
    /**
     * 1. Arahkan User ke Popup Resmi Facebook Login
     */
    public function redirect(Office $office)
    {
        $appId = config('services.meta.app_id') ?: env('META_APP_ID');
        $redirectUri = url('/auth/facebook/callback');

        // Simpan slug kantor di session agar saat redirect balik, sistem tahu kantor mana yang menghubungkan
        session(['oauth_office_slug' => $office->slug]);

        // Daftar Izin Lengkap untuk FB & IG
        $permissions = [
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
            'pages_manage_comments',
            'pages_messaging',
            'pages_manage_metadata',
            'instagram_basic',
            'instagram_manage_comments',
            'instagram_manage_messages',
        ];

        $loginUrl = "https://www.facebook.com/v19.0/dialog/oauth?" . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $permissions),
            'response_type' => 'code',
            'state' => Str::random(20),
        ]);

        return redirect()->away($loginUrl);
    }

    /**
     * 2. Tangkap Kode Balasan & Tukar Menjadi Page Access Token ABADI (Lifetime)
     */
    public function callback(Request $request, MetaGraphService $meta)
    {
        $code = $request->query('code');
        $officeSlug = session('oauth_office_slug');

        if (!$code || !$officeSlug) {
            return redirect('/admin')->with('error', 'Otorisasi Facebook dibatalkan.');
        }

        $office = Office::where('slug', $officeSlug)->first();
        if (!$office) return redirect('/admin');

        $appId = config('services.meta.app_id') ?: env('META_APP_ID');
        $appSecret = config('services.meta.app_secret') ?: env('META_APP_SECRET');
        $redirectUri = url('/auth/facebook/callback');

        try {
            // 🎯 LANGKAH A: Tukar Kode Otorisasi menjadi User Access Token Sementara
            $tokenRes = Http::get("https://graph.facebook.com/v19.0/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ])->json();

            $shortUserToken = $tokenRes['access_token'] ?? null;
            if (!$shortUserToken) {
                Log::error("[OAuth Error] Gagal mendapatkan user token: " . json_encode($tokenRes));
                return redirect("/admin/{$office->slug}/channels")->with('error', 'Gagal login ke Facebook.');
            }

            // 🎯 LANGKAH B: Tukar menjadi Long-Lived User Token (60 Hari)
            $longTokenRes = Http::get("https://graph.facebook.com/v19.0/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortUserToken,
            ])->json();

            $longUserToken = $longTokenRes['access_token'] ?? $shortUserToken;

            // 🎯 LANGKAH C: Ambil Daftar Halaman FB Menggunakan Long-Lived Token
            // 🔥 RAHASIA META: Token Halaman yang dihasilkan dari Long-Lived User Token adalah TOKEN ABADI (NEVER EXPIRES)!
            $accountsRes = Http::get("https://graph.facebook.com/v19.0/me/accounts", [
                'access_token' => $longUserToken,
                'fields' => 'id,name,access_token,instagram_business_account',
            ])->json();

            $pages = $accountsRes['data'] ?? [];

            if (empty($pages)) {
                return redirect("/admin/{$office->slug}/channels")->with('error', 'Tidak ada Halaman Facebook yang ditemukan/diberi izin.');
            }

            $connectedCount = 0;

            // 🎯 LANGKAH D: Simpan Otomatis Setiap Halaman FB & Akun IG ke Database
            foreach ($pages as $p) {
                $pageId = $p['id'];
                $pageName = $p['name'];
                $lifetimePageToken = $p['access_token']; // 🔥 TOKEN PERMANEN / ABADI

                // 1. Hubungkan / Daftarkan Webhook ke Meta secara instan
                $meta->subscribePageWebhook($pageId, $lifetimePageToken);

                // 2. Simpan atau Update Channel Facebook di Database Kantor Ini
                Channel::updateOrCreate(
                    [
                        'office_id' => $office->id,
                        'identifier' => $pageId,
                    ],
                    [
                        'type' => 'facebook',
                        'name' => "FB: {$pageName}",
                        'credentials' => [
                            'access_token' => $lifetimePageToken,
                            'auto_first_comment' => 'Selamat datang! Dapatkan promo spesial hari ini dengan menghubungi kami.',
                        ],
                        'status' => 'connected',
                        'is_bot_enabled' => true,
                    ]
                );
                $connectedCount++;

                // 3. Cek Jika Ada Instagram Bisnis yang Terhubung ke Halaman Ini
                if (!empty($p['instagram_business_account']['id'])) {
                    $igId = $p['instagram_business_account']['id'];

                    Channel::updateOrCreate(
                        [
                            'office_id' => $office->id,
                            'identifier' => $igId,
                        ],
                        [
                            'type' => 'instagram',
                            'name' => "IG: {$pageName}",
                            'credentials' => [
                                'access_token' => $lifetimePageToken, // Menggunakan Page Token yang sama
                            ],
                            'status' => 'connected',
                            'is_bot_enabled' => true,
                        ]
                    );
                    $connectedCount++;
                }
            }

            return redirect("/admin/{$office->slug}/channels")->with('success', "Sukses! {$connectedCount} Saluran (FB/IG) berhasil terhubung otomatis dengan Token Abadi!");

        } catch (\Exception $e) {
            Log::error("[OAuth Exception] " . $e->getMessage());
            return redirect("/admin/{$office->slug}/channels")->with('error', 'Terjadi kesalahan sistem saat menghubungkan Facebook.');
        }
    }
}
