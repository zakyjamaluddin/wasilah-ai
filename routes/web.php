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
    // 1. Ambil Kantor Mutamtour Babat (Office ID 4)
    $office = Office::find(4);

    if (!$office) {
        return response()->json(['error' => 'Kantor ID 4 tidak ditemukan'], 404);
    }

    // 2. Jalankan Auto-Sync & Simpan Token Cara V1
    $channel = $composio->autoSyncOfficeChannel($office, 'facebook');

    // Ambil token secara aman dengan pengaman ?? null
    $credentials = $channel?->credentials ?? [];
    $token = $credentials['access_token'] ?? null;

    return response()->json([
        'pesan'           => 'Pengecekan Halaman Mutamtour Babat Selesai!',
        'office_name'     => $office->name,
        'channel_name'    => $channel?->name,
        'page_id'         => $channel?->identifier,
        'has_token_in_db' => !empty($token),
        'token_preview'   => $token ? substr($token, 0, 15) . '...' : 'Token belum tersimpan / diredact oleh Composio',
        'status'          => $channel?->status,
    ], 200, [], JSON_PRETTY_PRINT);
});
