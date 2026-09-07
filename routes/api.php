<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\MetaWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);


// Webhook Meta (Facebook & Instagram)
Route::get('/meta/webhook', [MetaWebhookController::class, 'verify']);
Route::post('/meta/webhook', [MetaWebhookController::class, 'handle']);
