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
    $appId = config('services.meta.client_id') ?? config('services.meta.app_id') ?? env('META_APP_ID');
    $appSecret = config('services.meta.client_secret') ?? config('services.meta.app_secret') ?? env('META_APP_SECRET');
    $appToken = ($appId && $appSecret) ? "{$appId}|{$appSecret}" : null;

    // 1. AMBIL CHANNEL ZAKY APPS (INSTAGRAM / FACEBOOK)
    $zakyChannel = Channel::where(function($q) {
        $q->where('name', 'like', '%zaky%')->orWhere('identifier', '17841469669611882')->orWhere('identifier', '422099137659003');
    })->whereNotNull('credentials')->first();

    // 2. AMBIL CHANNEL MUTAMTOUR BABAT (INSTAGRAM / FACEBOOK)
    $babatChannel = Channel::where(function($q) {
        $q->where('name', 'like', '%babat%')->orWhere('identifier', '17841461951008043')->orWhere('identifier', '132125273311890');
    })->whereNotNull('credentials')->first();

    $report = [];

    // FUNGSI HELPER INSPEKSI CHANNEL
    $inspectChannel = function($channel, $label) use ($appToken) {
        if (!$channel) {
            return ['status' => 'Channel tidak ditemukan di database'];
        }

        $token = $channel->credentials['access_token'] ?? null;
        $identifier = $channel->identifier;

        // A. Cek Info Akun dari Meta
        $accountInfo = null;
        if ($token && $identifier) {
            $accountInfo = Http::timeout(8)->get("https://graph.facebook.com/v21.0/{$identifier}", [
                'fields'       => 'id,name,username,is_published',
                'access_token' => $token,
            ])->json();
        }

        // B. Cek Izin Token (Scopes & Permissions) dari Meta Debug Token
        $tokenScopes = null;
        if ($token && $appToken) {
            $tokenScopes = Http::timeout(8)->get("https://graph.facebook.com/v21.0/debug_token", [
                'input_token'  => $token,
                'access_token' => $appToken,
            ])->json('data.scopes');
        }

        // C. Tarik Obrolan/Pesan Terakhir di Inbox
        $inboxConvs = null;
        if ($token && $identifier) {
            $inboxConvs = Http::timeout(8)->get("https://graph.facebook.com/v21.0/{$identifier}/conversations", [
                'fields'       => 'id,updated_time,participants,messages.limit(2){id,message,from,to,created_time}',
                'access_token' => $token,
            ])->json();
        }

        return [
            'label'                => $label,
            'database_record'      => [
                'id'          => $channel->id,
                'office_id'   => $channel->office_id,
                'name'        => $channel->name,
                'type'        => $channel->type,
                'identifier'  => $channel->identifier,
                'status'      => $channel->status,
                'has_token'   => !empty($token),
                'token_start' => $token ? substr($token, 0, 15) . '...' : null,
            ],
            'meta_account_info'    => $accountInfo,
            'granted_token_scopes' => $tokenScopes,
            'recent_conversations' => $inboxConvs,
        ];
    };

    $report['1_zaky_apps_audit'] = $inspectChannel($zakyChannel, 'ZAKY APPS (SUKSES)');
    $report['2_mutamtour_babat_audit'] = $inspectChannel($babatChannel, 'MUTAMTOUR BABAT (BELUM BALAS)');

    // 3. UJI COBA TEMBAK BALASAN KE PESAN TERAKHIR MUTAMTOUR BABAT
    $babatToken = $babatChannel?->credentials['access_token'] ?? null;
    $babatId = $babatChannel?->identifier;
    $sendTestResult = null;

    if ($babatToken && $babatId) {
        // Cari pesan terakhir dari customer di inbox Mutamtour Babat
        $convs = $report['2_mutamtour_babat_audit']['recent_conversations']['data'] ?? [];
        $firstConv = $convs[0] ?? null;
        $latestMessage = $firstConv['messages']['data'][0] ?? null;
        $senderId = null;

        if ($latestMessage) {
            $senderId = $latestMessage['from']['id'] ?? null;
            // Jika sender bukan akun IG Babat sendiri
            if ($senderId === $babatId) {
                $senderId = $latestMessage['to']['data'][0]['id'] ?? null;
            }
        }

        if ($senderId) {
            $testMsg = "Halo! Ini adalah tes balasan resmi langsung dari server Wasilah AI ke Instagram Mutamtour Babat (" . now()->format('H:i:s') . ").";

            // Eksekusi kirim balasan langsung via Meta Graph API
            $sendRes = Http::timeout(10)->post("https://graph.facebook.com/v21.0/me/messages?access_token={$babatToken}", [
                'recipient' => ['id' => (string) $senderId],
                'message'   => ['text' => $testMsg],
            ]);

            $sendTestResult = [
                'target_sender_id' => $senderId,
                'pesan_terakhir_ditemukan' => $latestMessage['message'] ?? null,
                'waktu_pesan_terakhir' => $latestMessage['created_time'] ?? null,
                'http_status' => $sendRes->status(),
                'response_from_meta' => $sendRes->json(),
            ];
        } else {
            $sendTestResult = 'Tidak ada pesan / sender customer yang ditemukan di inbox percakapan Mutamtour Babat.';
        }
    }

    $report['3_babat_outbound_reply_test'] = $sendTestResult;

    return response()->json($report, 200, [], JSON_PRETTY_PRINT);
});
