<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LynkIdEmailWebhookController extends Controller
{
    /**
     * Menerima Webhook Email Lynk.id (Dilengkapi Kunci Anti-Duplikasi / Idempotency)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('📩 [Lynk.id Email Webhook Received]:', $payload);

        $data = $payload['data'] ?? $payload;
        $subject = $data['subject'] ?? $data['snippet'] ?? '';

        // 🛡️ 1. KUNCI ANTI-DUPLIKASI (IDEMPOTENCY GUARD)
        // Gunakan ID unik pesan email dari Gmail/Composio agar 1 email HANYA diproses 1 KALI
        $emailMessageId = $data['id'] ?? $data['message_id'] ?? $data['thread_id'] ?? md5($subject . ($data['snippet'] ?? '') . date('Y-m-d'));
        $lockKey = "lynkid_processed_msg_{$emailMessageId}";

        if (Cache::has($lockKey)) {
            Log::info("ℹ️ [Lynk.id Ignored] Email ID {$emailMessageId} sudah pernah diproses sebelumnya. Melewati penambahan hari.");
            return response()->json(['status' => 'ignored', 'reason' => 'Already processed'], 200);
        }

        // Kunci selama 7 hari agar email yang sama tidak bisa diproses ulang
        Cache::put($lockKey, true, now()->addDays(7));

        // 2. BERSIHKAN TEKS DARI TAG HTML
        $rawBody = $data['body'] ?? $data['html'] ?? $data['text'] ?? $data['snippet'] ?? json_encode($data);
        $cleanText = html_entity_decode(strip_tags($rawBody));
        $fullSearchContent = strtolower($subject . ' ' . $cleanText);

        // 3. EKSTRAK ALAMAT EMAIL PEMBELI
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $cleanText, $matches);
        $allEmailsFound = array_unique($matches[0] ?? []);

        $ignoredDomains = ['lynk.id', 'google.com', 'composio.dev'];
        $buyerEmail = null;

        foreach ($allEmailsFound as $email) {
            $emailLower = strtolower(trim($email));
            $isIgnored = false;

            foreach ($ignoredDomains as $domain) {
                if (str_contains($emailLower, $domain)) {
                    $isIgnored = true;
                    break;
                }
            }

            if (!$isIgnored) {
                $buyerEmail = $emailLower;
                break;
            }
        }

        // Fallback: Cek dengan order pending di database
        if (!$buyerEmail) {
            $pendingOrders = SubscriptionOrder::where('status', 'pending')
                ->where('created_at', '>=', now()->subHours(48))
                ->get();

            foreach ($pendingOrders as $pOrder) {
                if (str_contains($fullSearchContent, strtolower($pOrder->customer_email))) {
                    $buyerEmail = strtolower($pOrder->customer_email);
                    break;
                }
            }
        }

        if (!$buyerEmail) {
            Log::warning('⚠️ [Lynk.id Webhook] Email pembeli tidak ditemukan di isi pesan.');
            return response()->json(['status' => 'ignored', 'reason' => 'Buyer email not detected'], 200);
        }

        // 4. DETEKSI PAKET (1 BULAN vs 1 TAHUN)
        $planType = '1_month';
        if (
            str_contains($fullSearchContent, '1 tahun') ||
            str_contains($fullSearchContent, '12 bulan') ||
            str_contains($fullSearchContent, '300.000') ||
            str_contains($fullSearchContent, '300000')
        ) {
            $planType = '1_year';
        }

        // 5. CARI ORDER PENDING
        $order = SubscriptionOrder::where('customer_email', $buyerEmail)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(48))
            ->latest('id')
            ->first();

        $office = null;
        if ($order) {
            $office = $order->office;
            $planType = $order->plan_type;
        } else {
            $user = User::where('email', $buyerEmail)->first();
            $office = $user?->offices()->latest('id')->first();
        }

        if (!$office) {
            Log::error("❌ [Lynk.id Error] Kantor untuk email {$buyerEmail} tidak ditemukan.");
            return response()->json(['status' => 'error', 'reason' => 'Office not found'], 404);
        }

        // 6. HITUNG PENAMBAHAN MASA AKTIF SECARA AMAN (ATOMIC TRANSACTION)
        $durationDays = $planType === '1_year' ? 365 : 30;

        DB::transaction(function () use ($order, $office, $durationDays, $data) {
            // Gunakan ->copy() agar objek Carbon tidak ter-mutasi secara tidak sengaja
            $currentExpiry = $office->expired_at ? $office->expired_at->copy() : null;

            if ($currentExpiry && $currentExpiry->isFuture()) {
                $newExpiry = $currentExpiry->addDays($durationDays);
            } else {
                $newExpiry = now()->addDays($durationDays);
            }

            // Update status & tanggal expired kantor
            $office->update([
                'subscription_status' => 'active',
                'expired_at'          => $newExpiry,
            ]);

            // Tandai order pending menjadi PAID
            if ($order) {
                $order->update([
                    'status'           => 'paid',
                    'paid_at'          => now(),
                    'payment_metadata' => $data,
                ]);
            }

            Log::info("🎉 [Lynk.id Sukses!] Kantor: {$office->name} berhasil diperpanjang tepat +{$durationDays} Hari. Berakhir pada: {$newExpiry->format('d M Y H:i')}");
        });

        return response()->json([
            'status'     => 'success',
            'office'     => $office->name,
            'plan'       => $planType,
            'expired_at' => $office->fresh()->expired_at?->format('Y-m-d H:i:s'),
        ]);
    }
}
