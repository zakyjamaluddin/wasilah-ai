<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use App\Services\PaymentGatewayService;


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