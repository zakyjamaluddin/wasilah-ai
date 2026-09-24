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
    $offices = Office::with(['channels' => function($q) {
        $q->where('type', 'facebook');
    }, 'composioAccount'])->get();

    $fullAuditReport = [];

    foreach ($offices as $office) {
        $channel = $office->channels->first();
        $connectionId = $channel?->composio_connection_id;
        $pageId = $channel?->identifier;

        $pageAccessToken = null;
        $allManagedPagesForOffice = [];
        $metaSubscribedApps = null;
        $pageDetails = null;

        // 1. Tarik Halaman & Token dari Composio jika ada akun Composio
        if ($office->composioAccount) {
            try {
                $pagesRes = $composio->executeAction($office, 'FACEBOOK_LIST_MANAGED_PAGES', [], $connectionId);
                $pagesData = $pagesRes['data']['data'] ?? $pagesRes['data']['response_data'] ?? [];
                $pages = isset($pagesData['data']) && is_array($pagesData['data']) ? $pagesData['data'] : (is_array($pagesData) ? $pagesData : []);

                foreach ($pages as $p) {
                    $allManagedPagesForOffice[] = [
                        'id'   => $p['id'] ?? null,
                        'name' => $p['name'] ?? null,
                    ];

                    if ((string)($p['id'] ?? '') === (string)$pageId) {
                        $pageAccessToken = $p['access_token'] ?? null;
                    }
                }

                // Jika pageAccessToken belum ketemu dari ID, ambil dari halaman pertama yang cocok
                if (!$pageAccessToken && !empty($pages)) {
                    $pageAccessToken = $pages[0]['access_token'] ?? null;
                }
            } catch (\Throwable $e) {
                $allManagedPagesForOffice = 'Error: ' . $e->getMessage();
            }
        }

        // 2. Jika ada Token Halaman, Tanya Langsung ke Meta Graph API
        if ($pageId && $pageAccessToken) {
            try {
                // Cek Subscribed Apps di Meta
                $subRes = Http::timeout(8)->get("https://graph.facebook.com/v21.0/{$pageId}/subscribed_apps", [
                    'access_token' => $pageAccessToken,
                ]);
                $metaSubscribedApps = $subRes->json();

                // Cek Status Halaman di Meta
                $infoRes = Http::timeout(8)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                    'fields'       => 'id,name,is_published,can_post',
                    'access_token' => $pageAccessToken,
                ]);
                $pageDetails = $infoRes->json();
            } catch (\Throwable $e) {
                $metaSubscribedApps = 'Meta Error: ' . $e->getMessage();
            }
        }

        $fullAuditReport[] = [
            'office_id'                => $office->id,
            'office_name'              => $office->name,
            'office_slug'              => $office->slug,
            'database_channel'         => [
                'id'                     => $channel?->id,
                'name'                   => $channel?->name,
                'type'                   => $channel?->type,
                'identifier_page_id'     => $channel?->identifier,
                'composio_connection_id' => $channel?->composio_connection_id,
                'status'                 => $channel?->status,
                'is_bot_enabled'         => $channel?->is_bot_enabled,
            ],
            'pages_returned_by_composio' => $allManagedPagesForOffice,
            'meta_graph_verification'  => [
                'has_token'              => !empty($pageAccessToken),
                'page_details'           => $pageDetails,
                'subscribed_apps_on_meta'=> $metaSubscribedApps,
            ]
        ];
    }

    return response()->json([
        'total_offices_audited' => count($fullAuditReport),
        'audit_results'         => $fullAuditReport,
    ], 200, [], JSON_PRETTY_PRINT);
});
