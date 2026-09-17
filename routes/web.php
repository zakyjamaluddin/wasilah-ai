<?php

use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Models\Channel;
use App\Models\Order;
use App\Services\PaymentGatewayService;
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

// 🔍 RUTE DIAGNOSA DUITKU LANGSUNG DI BROWSER
Route::get('/test-duitku', function (PaymentGatewayService $gateway) {
    $order = Order::latest()->first();
    if (!$order) {
        return 'Belum ada data Order di database. Silakan isi form checkout terlebih dahulu.';
    }

    $merchantCode = config('services.duitku.merchant_code') ?: env('DUITKU_MERCHANT_CODE', '');
    $apiKey = config('services.duitku.api_key') ?: env('DUITKU_API_KEY', '');
    $apiUrl = config('services.duitku.api_url');

    $amount = (int) $order->amount;
    $orderId = $order->invoice_number;
    $signature = md5($merchantCode . $orderId . $amount . $apiKey);

    $payload = [
        'merchantCode'     => $merchantCode,
        'paymentAmount'    => $amount,
        'merchantOrderId'  => $orderId,
        'productDetails'   => "Paket Wasilah AI: " . $order->plan_name,
        'email'            => $order->customer_email,
        'customerVaName'   => $order->customer_name,
        'callbackUrl'      => url('/api/payment/webhook'),
        'returnUrl'        => route('checkout.invoice', ['invoice' => $order->invoice_number]),
        'signature'        => $signature,
        'expiryPeriod'     => 1440,
    ];

    $response = Illuminate\Support\Facades\Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ])->timeout(20)->post($apiUrl, $payload);

    return response()->json([
        'URL_DITARGET'    => $apiUrl,
        'MERCHANT_CODE'   => $merchantCode,
        'SIGNATURE_MD5'   => $signature,
        'HTTP_STATUS'     => $response->status(),
        'RESPON_DARI_DUITKU' => $response->json() ?? $response->body(),
    ]);
});


Route::get('/test-instagram', function () {
    // 1. Ambil Channel Instagram di Database
    $channel = Channel::where('type', 'instagram')->latest()->first();

    if (!$channel) {
        return response()->json(['error' => 'Belum ada Channel bertipe Instagram di database!'], 404);
    }

    $token = $channel->credentials['access_token'] ?? '';
    $savedIgId = $channel->identifier;

    // 2. Ambil Channel Facebook pasangannya untuk mencari ID Instagram Bisnis yang Asli
    $fbChannel = Channel::where('type', 'facebook')->latest()->first();
    $pageId = $fbChannel ? $fbChannel->identifier : null;

    $realIgData = null;
    if ($pageId && $token) {
        $realIgData = Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v19.0/{$pageId}?fields=instagram_business_account,name&access_token={$token}")->json();
    }

    // 3. Cek Izin Token Instagram di Server Meta
    $permissionsRes = Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v19.0/me/permissions?access_token={$token}")->json();
    $grantedPermissions = collect($permissionsRes['data'] ?? [])->where('status', 'granted')->pluck('permission')->toArray();

    // 4. Cek apakah ID di Database sudah cocok dengan ID Asli Instagram
    $actualIgId = $realIgData['instagram_business_account']['id'] ?? 'TIDAK_DITEMUKAN';
    $isIdMatched = ($savedIgId === $actualIgId);

    return response()->json([
        '1_STATUS_BOT_DI_DB'         => $channel->is_bot_enabled ? 'AKTIF (ON)' : 'MATI (OFF)',
        '2_ID_INSTAGRAM_DI_DATABASE' => $savedIgId,
        '3_ID_INSTAGRAM_ASLI_META'   => $actualIgId,
        '4_APAKAH_ID_COCOK'          => $isIdMatched ? '✅ COCOK (SIAP TERIMA PESAN)' : '❌ BEDA / SALAH ID (Penyebab Pesan Tidak Dibalas)',
        '5_IZIN_TOKEN_INSTAGRAM'     => [
            'instagram_basic'            => in_array('instagram_basic', $grantedPermissions) ? '✅ ADA' : '❌ KURANG',
            'instagram_manage_messages' => in_array('instagram_manage_messages', $grantedPermissions) ? '✅ ADA' : '❌ KURANG',
            'instagram_manage_comments' => in_array('instagram_manage_comments', $grantedPermissions) ? '✅ ADA' : '❌ KURANG',
        ],
        '6_DETAIL_AKUN_META'         => $realIgData,
    ]);
});


use App\Models\Office;
use App\Services\ComposioService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


Route::get('/debug-composio', function () {
    $office = Office::with('composioAccount')->first();
    $apiKey = $office->composioAccount?->api_key;
    $userId = "office_{$office->id}_{$office->slug}";
    $connectedAccountId = "ca_5EcQoe_tKKo4"; // Sesuai sesi Anda

    $client = Http::withHeaders([
        'x-api-key' => $apiKey,
        'Content-Type' => 'application/json',
    ]);

    $enableResults = [];

    // JIKA DIKLIK ?enable=1, KITA TEMBAK LANGSUNG PEMBUATAN TRIGGERNYA
    if (request()->has('enable')) {
        // Slug umum untuk event Facebook di Composio
        $targetSlugs = [
            'facebook_new_message',
            'facebook_new_comment'
        ];

        foreach ($targetSlugs as $slug) {
            // Memanggil API Create/Enable Trigger dengan spesifik User ID & Account ID
            $res = $client->post("https://backend.composio.dev/api/v3.1/triggers/enable", [
                'user_id' => $userId,
                'trigger_slug' => $slug,
                'connected_account_id' => $connectedAccountId,
            ]);

            $enableResults[$slug] = [
                'status' => $res->status(),
                'response' => $res->json(),
            ];
        }
    }

    // CEK TRIGGER YANG SEDANG AKTIF SAAT INI
    $activeRes = $client->get("https://backend.composio.dev/api/v3.1/triggers/active", [
        'user_id' => $userId,
    ]);

    return response()->json([
        'office_user_id' => $userId,
        'connected_account_id' => $connectedAccountId,
        'enable_action_results' => $enableResults,
        'current_active_triggers' => $activeRes->json(),
        'panduan_tindakan' => 'Buka https://wasilah-ai.my.id/debug-composio?enable=1 untuk mengaktifkan trigger secara spesifik.'
    ], 200, [], JSON_PRETTY_PRINT);
});

