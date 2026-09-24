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


Route::get('/debug-composio', function (ComposioService $composio) {
    $babatPageId = "132125273311890"; // ID Mutamtour Babat
    $office = Office::find(4); // Kantor Mutamtour Babat

    if (!$office) {
        return response()->json(['error' => 'Kantor ID 4 tidak ditemukan'], 404);
    }

    $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
    $connectionId = $channel?->composio_connection_id;

    // 1. Eksekusi Tool FACEBOOK_LIST_MANAGED_PAGES
    $pagesRes = $composio->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES', [], $connectionId);

    // 2. Ekstrak Array Pages dari Nesting Composio yang Tepat
    $pages = $pagesRes['data']['data']['data']
        ?? $pagesRes['data']['data']
        ?? $pagesRes['data']['response_data']
        ?? [];

    $babatPageData = null;
    foreach ($pages as $p) {
        if ((string)($p['id'] ?? '') === (string)$babatPageId) {
            $babatPageData = $p;
            break;
        }
    }

    $pageAccessToken = $babatPageData['access_token'] ?? null;
    $pageName = $babatPageData['name'] ?? 'Mutamtour Babat';

    $subscribeResult = null;

    // 3. JIKA TOKEN DITEMUKAN, SIMPAN KE DATABASE & DAFTARKAN CARA V1
    if ($pageAccessToken && $channel) {
        $credentials = $channel->credentials ?? [];
        $credentials['access_token'] = $pageAccessToken;

        // Update database channel
        $channel->update([
            'identifier'  => (string) $babatPageId,
            'name'        => "FB: {$pageName}",
            'credentials' => $credentials,
            'status'      => 'connected',
        ]);

        // Daftarkan Webhook Cara V1 ke Meta
        try {
            $meta = app(MetaGraphService::class);
            $subscribeResult = $meta->subscribePageWebhook($babatPageId, $pageAccessToken, 'facebook');
        } catch (\Throwable $e) {
            $subHttp = Http::post("https://graph.facebook.com/v21.0/{$babatPageId}/subscribed_apps", [
                'subscribed_fields' => 'messages,messaging_postbacks,feed,message_deliveries,message_reads',
                'access_token'      => $pageAccessToken,
            ]);
            $subscribeResult = $subHttp->json();
        }
    }

    return response()->json([
        'halaman_ditemukan'  => $babatPageData ? true : false,
        'page_name'          => $pageName,
        'page_id'            => $babatPageId,
        'has_access_token'   => !empty($pageAccessToken),
        'token_preview'      => $pageAccessToken ? substr($pageAccessToken, 0, 20) . '...' : 'Tidak ditemukan di respon',
        'subscribe_v1_hasil' => $subscribeResult,
        'channel_db_status'  => $channel?->fresh(),
    ], 200, [], JSON_PRETTY_PRINT);
});
