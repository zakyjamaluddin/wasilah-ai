<?php

use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Models\Channel;
use App\Models\Office;
use App\Models\Order;
use App\Services\ComposioService;
use App\Services\MetaGraphService;
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


Route::get('/debug-composio', function () {
    // Ambil Channel Instagram yang ada di database
    $igChannel = Channel::where('type', 'instagram')->whereNotNull('credentials')->latest('id')->first();

    if (!$igChannel) {
        return response()->json(['error' => 'Belum ada Channel Instagram dengan kredensial token di database.'], 404);
    }

    $token = $igChannel->credentials['access_token'] ?? null;
    $identifier = $igChannel->identifier;

    // 1. Cek info akun Instagram dari Token
    $verifyRes = Http::get("https://graph.facebook.com/v21.0/{$identifier}", [
        'fields'       => 'id,name,username,profile_picture_url',
        'access_token' => $token,
    ])->json();

    // 2. Tarik daftar akun Instagram yang terikat pada token ini via /me/accounts
    $accountsRes = Http::get("https://graph.facebook.com/v21.0/me/accounts", [
        'fields'       => 'id,name,instagram_business_account{id,username,name}',
        'access_token' => $token,
    ])->json();

    return response()->json([
        'channel_in_db'             => [
            'name'       => $igChannel->name,
            'identifier' => $igChannel->identifier,
        ],
        'token_verification_result' => $verifyRes,
        'linked_instagram_accounts' => $accountsRes,
    ], 200, [], JSON_PRETTY_PRINT);
});

