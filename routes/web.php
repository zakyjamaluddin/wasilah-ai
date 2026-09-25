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



Route::get('/debug-composio', function (MetaGraphService $meta) {
    // 1. Ambil Channel Instagram Mutamtour Babat
    $igChannel = Channel::where('type', 'instagram')
        ->where('identifier', '17841461951008043')
        ->first();

    if (!$igChannel) {
        $igChannel = Channel::where('type', 'instagram')->whereNotNull('credentials')->first();
    }

    $token = $igChannel->credentials['access_token'] ?? null;
    $targetIgsid = "1551001142903584"; // ID Instagram Penerima (Zaky Apps / Akun Penguji)

    $testReply = "Halo Kak! Ini adalah tes balasan resmi langsung dari Instagram @mutamtour.babat via Meta Graph API (" . now()->format('H:i:s') . ").";

    // 2. Eksekusi Kirim DM Instagram via MetaGraphService
    $sendResult = $meta->sendInstagramDmReply($token, $targetIgsid, $testReply);

    return response()->json([
        'instagram_account' => $igChannel?->name,
        'identifier'        => $igChannel?->identifier,
        'target_recipient'  => $targetIgsid,
        'send_dm_result'    => $sendResult,
    ], 200, [], JSON_PRETTY_PRINT);
});
