<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\MetaWebhookController;
use App\Http\Controllers\Api\PaymentWebhookController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);


// Webhook Meta (Facebook & Instagram)
Route::get('/meta/webhook', [MetaWebhookController::class, 'verify']);
Route::post('/meta/webhook', [MetaWebhookController::class, 'handle']);


// Webhook Pembayaran Midtrans
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle']);
