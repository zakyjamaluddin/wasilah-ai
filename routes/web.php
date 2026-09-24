<?php

use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\CheckoutController;
use App\Livewire\Workspace\OmnichannelWorkspace;
use App\Models\Channel;
use App\Models\Office;
use App\Models\Order;
use App\Services\ComposioService;
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
    // 1. Cari Kantor Mutamtour Babat
    $office = Office::where('slug', 'like', '%babat%')->orWhere('name', 'like', '%babat%')->first();

    if (!$office) {
        $office = Office::latest('id')->first();
    }

    $channel = Channel::where('office_id', $office->id)->where('type', 'facebook')->first();
    $connectionId = $channel?->composio_connection_id;
    $pageId = $channel?->identifier;

    // 2. Tarik Data Halaman & Token dari Composio
    $pagesRes = $composio->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES', [], $connectionId);
    $pagesData = $pagesRes['data']['data'] ?? $pagesRes['data']['response_data'] ?? [];
    $pages = isset($pagesData['data']) && is_array($pagesData['data']) ? $pagesData['data'] : (is_array($pagesData) ? $pagesData : []);

    $targetPage = null;
    foreach ($pages as $p) {
        if ((string)$p['id'] === (string)$pageId) {
            $targetPage = $p;
            break;
        }
    }

    $pageAccessToken = $targetPage['access_token'] ?? null;

    $metaCheck = [];
    if ($pageId && $pageAccessToken) {
        // A. Cek detail info Halaman dari Meta
        $pageInfo = Http::get("https://graph.facebook.com/v21.0/{$pageId}", [
            'fields'       => 'id,name,is_published,can_post,tasks',
            'access_token' => $pageAccessToken,
        ])->json();

        // B. Cek daftar aplikasi yang me-langgan (Subscribed Apps) di Halaman ini
        $subscribedApps = Http::get("https://graph.facebook.com/v21.0/{$pageId}/subscribed_apps", [
            'access_token' => $pageAccessToken,
        ])->json();

        // C. Paksa subscribe ulang dengan SEMUA field lengkap
        $forceSubscribe = Http::post("https://graph.facebook.com/v21.0/{$pageId}/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks,message_reads,message_deliveries,message_echoes,feed',
            'access_token'      => $pageAccessToken,
        ])->json();

        $metaCheck = [
            'page_info_from_meta'       => $pageInfo,
            'current_subscribed_apps'   => $subscribedApps,
            'force_re_subscribe_result' => $forceSubscribe,
        ];
    }

    return response()->json([
        'office_name'       => $office->name,
        'page_id'           => $pageId,
        'channel_name'      => $channel?->name,
        'has_access_token'  => !empty($pageAccessToken),
        'meta_graph_report' => $metaCheck,
    ], 200, [], JSON_PRETTY_PRINT);
});
