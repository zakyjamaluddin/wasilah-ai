<?php

use App\Http\Controllers\Api\ComposioWebhookController;
use App\Http\Controllers\Api\MetaWebhookController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LynkIdEmailWebhookController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);


// Webhook Meta (Facebook & Instagram)
Route::get('/meta/webhook', [MetaWebhookController::class, 'verify']);
Route::post('/meta/webhook', [MetaWebhookController::class, 'handle']);


// Webhook Pembayaran Midtrans
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle']);

// Endpoint Webhook Penerima Event Composio (FB & Instagram)
Route::post('/composio/webhook', [ComposioWebhookController::class, 'handle'])->name('api.composio.webhook');

// Webhook Otomasi Pembayaran Lynk.id via Email Listener
Route::post('/payment/lynkid-webhook', [LynkIdEmailWebhookController::class, 'handle'])->name('api.payment.lynkid');
