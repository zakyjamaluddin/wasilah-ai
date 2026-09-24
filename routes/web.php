<?php

use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Models\Channel;
use App\Models\Office;
use App\Models\Order;
use App\Services\ComposioService;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/kebijakan-privasi', function () {
    return view('privacy');
})->name('privacy');


Route::get('/workspace/{office:slug}/crm', OmnichannelWorkspace::class)
    ->middleware(['auth'])
    ->name('workspace.crm');


// 🔥 1-CLICK META OAUTH ROUTES
Route::get('/workspace/{office:slug}/auth/facebook', [FacebookOAuthController::class, 'redirect'])
    ->name('auth.facebook.redirect');
Route::get('/auth/facebook/callback', [FacebookOAuthController::class, 'callback'])
    ->name('auth.facebook.callback');


// 🔥 CHECKOUT & REGISTRATION ROUTES
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/checkout/invoice/{invoice}', [CheckoutController::class, 'invoice'])->name('checkout.invoice');
Route::post('/checkout/pay/{invoice}', [CheckoutController::class, 'pay'])->name('checkout.pay');



Route::get('/debug-composio', function (ComposioService $composio) {
    // 1. Ambil Kredensial Meta App Pribadi Anda dari config/services.php
    $appId = config('services.meta.client_id') ?? config('services.meta.app_id') ?? env('META_APP_ID');
    $appSecret = config('services.meta.client_secret') ?? config('services.meta.app_secret') ?? env('META_APP_SECRET');

    $babatPageId = "132125273311890"; // ID Mutamtour Babat
    $office = Office::find(4); // Kantor Mutamtour Babat

    // 2. Tarik Token Halaman Babat dari Composio
    $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
    $pagesRes = $composio->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES', [], $channel?->composio_connection_id);
    $pages = $pagesRes['data']['data'] ?? $pagesRes['data']['response_data'] ?? [];
    $pagesArray = isset($pages['data']) ? $pages['data'] : (is_array($pages) ? $pages : []);

    $pageAccessToken = null;
    foreach ($pagesArray as $p) {
        if ((string)$p['id'] === $babatPageId) {
            $pageAccessToken = $p['access_token'] ?? null;
            break;
        }
    }

    $results = [];

    // 3. EKSEKUSI PENDAFTARAN KE APLIKASI META PRIBADI ANDA OTOMATIS
    if ($appId && $appSecret) {
        $appToken = "{$appId}|{$appSecret}";

        // Tembak Graph API untuk mendaftarkan Babat ke App Pribadi Anda
        $subscribeToMyApp = Http::post("https://graph.facebook.com/v21.0/{$babatPageId}/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks,feed,message_deliveries,message_reads',
            'access_token'      => $pageAccessToken ?: $appToken,
        ])->json();

        $results['subscribe_to_my_meta_app_result'] = $subscribeToMyApp;
    } else {
        $results['subscribe_to_my_meta_app_result'] = 'META_APP_ID atau META_APP_SECRET belum ditemukan di .env / config.';
    }

    // 4. CEK ULANG DAFTAR SUBSCRIBED APPS DI META UNTUK HALAMAN BABAT
    $subscribedAppsNow = Http::get("https://graph.facebook.com/v21.0/{$babatPageId}/subscribed_apps", [
        'access_token' => $pageAccessToken,
    ])->json();

    $results['current_subscribed_apps_after_fix'] = $subscribedAppsNow;

    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});
