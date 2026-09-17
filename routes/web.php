<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use App\Services\PaymentGatewayService;
use App\Models\Channel;
use App\Models\Office;
use App\Models\Contact;
use App\Models\Conversation;
use App\Services\GeminiAiService;
use App\Services\ComposioService;

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




Route::get('/debug-composio', function (GeminiAiService $gemini, ComposioService $composio) {
    $results = [];

    // 1. CEK KANTOR & AKUN COMPOSIO
    $office = Office::with('composioAccount')->first();
    $results['1_office'] = [
        'id' => $office?->id,
        'name' => $office?->name,
        'composio_account_id' => $office?->composio_account_id,
        'has_api_key' => !empty($office?->composioAccount?->api_key),
        'api_key_masked' => $office?->composioAccount?->api_key ? substr($office->composioAccount->api_key, 0, 8) . '...' : null,
    ];

    // 2. CEK CHANNEL FACEBOOK
    $channel = Channel::where('office_id', $office?->id)->where('type', 'facebook')->first();
    $results['2_channel_facebook'] = [
        'found' => $channel ? true : false,
        'name' => $channel?->name,
        'status' => $channel?->status,
        'is_bot_enabled' => $channel?->is_bot_enabled,
    ];

    // 3. CEK KONTAK TERAKHIR
    $contact = Contact::where('office_id', $office?->id)->latest('id')->first();
    $results['3_latest_contact'] = [
        'id' => $contact?->id,
        'name' => $contact?->name,
        'fb_user_id' => $contact?->fb_user_id,
        'pipeline_stage' => $contact?->pipeline_stage,
    ];

    // 4. TEST GEMINI AI RESPONSE
    $conv = Conversation::where('office_id', $office?->id)->latest('id')->first();
    if ($conv) {
        try {
            $aiReply = $gemini->generateReply($conv);
            $results['4_gemini_test'] = [
                'success' => true,
                'reply' => $aiReply,
            ];
        } catch (\Throwable $e) {
            $results['4_gemini_test'] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    } else {
        $results['4_gemini_test'] = 'Belum ada percakapan (Conversation) di database.';
    }

    // 5. TEST TEMBAK PESAN BALASAN COMPOSIO KE FB USER
    $recipientId = $contact?->fb_user_id;
    if ($office && $recipientId) {
        $testMsg = "Halo! Ini adalah tes balasan manual dari Wasilah AI Debugger (" . now()->format('H:i:s') . ")";
        $sendResult = $composio->sendFacebookMessenger($office, $recipientId, $testMsg);
        $results['5_composio_send_test'] = $sendResult;
    } else {
        $results['5_composio_send_test'] = 'Tidak dapat mengetes kirim: Kontak atau fb_user_id belum ditemukan.';
    }

    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});