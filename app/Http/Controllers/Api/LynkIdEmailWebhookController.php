<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\SubscriptionOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LynkIdEmailWebhookController extends Controller
{
    /**
     * Menerima Webhook Email Lynk.id dari Composio Gmail Listener
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('📩 [Lynk.id Email Webhook Received]:', $payload);

        // Ekstrak isi teks email dan subjek email
        $data = $payload['data'] ?? $payload;
        $subject = $data['subject'] ?? $data['snippet'] ?? '';
        $bodyText = $data['body'] ?? $data['text'] ?? $data['message'] ?? $data['snippet'] ?? json_encode($data);

        // 1. Ekstrak Alamat Email Pembeli dari Isi Email Lynk.id
        // Format umum: "Email Pembeli: user@domain.com" atau mencari pola regex email
        preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $bodyText, $matches);
        $buyerEmail = $matches[0] ?? null;

        if (!$buyerEmail) {
            Log::warning('⚠️ [Lynk.id Webhook] Tidak menemukan alamat email pembeli di isi pesan.');
            return response()->json(['status' => 'ignored', 'reason' => 'No email found in body'], 200);
        }

        $cleanEmail = strtolower(trim($buyerEmail));

        // 2. Deteksi Paket (1 Bulan vs 1 Tahun) berdasarkan isi email/nama produk
        $planType = '1_month';
        if (str_contains(strtolower($bodyText), '1 tahun') || str_contains(strtolower($subject), '1 tahun') || str_contains(strtolower($bodyText), '300.000') || str_contains(strtolower($bodyText), '300000')) {
            $planType = '1_year';
        }

        Log::info("🔍 [Lynk.id Matching] Mencari Order Pending untuk Email: {$cleanEmail}, Paket: {$planType}");

        // 3. Cari Order Pending Terakhir untuk Email Ini (Dalam 48 Jam Terakhir)
        $order = SubscriptionOrder::where('customer_email', $cleanEmail)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(48))
            ->latest('id')
            ->first();

        // Fallback: Jika tidak ada pending order, cari kantor milik user dengan email tersebut
        $office = null;
        if ($order) {
            $office = $order->office;
            $planType = $order->plan_type;
        } else {
            $user = \App\Models\User::where('email', $cleanEmail)->first();
            $office = $user?->offices()->latest('id')->first();
        }

        if (!$office) {
            Log::error("❌ [Lynk.id Matching Gagal] Tidak menemukan kantor/order untuk email: {$cleanEmail}");
            return response()->json(['status' => 'error', 'reason' => 'No matching order or office found'], 404);
        }

        // 4. Hitung Penambahan Masa Aktif
        $durationDays = $planType === '1_year' ? 365 : 30;
        $currentExpiry = $office->expired_at;

        // Jika kantor masih aktif belum expired, tambahkan dari tanggal expired sebelumnya
        if ($currentExpiry && $currentExpiry->isFuture()) {
            $newExpiry = $currentExpiry->addDays($durationDays);
        } else {
            $newExpiry = now()->addDays($durationDays);
        }

        // 5. Update Status Kantor Menjadi Aktif!
        $office->update([
            'subscription_status' => 'active',
            'expired_at'          => $newExpiry,
        ]);

        // 6. Tandai Order sebagai Lunas (Paid)
        if ($order) {
            $order->update([
                'status'           => 'paid',
                'paid_at'          => now(),
                'payment_metadata' => $data,
            ]);
        }

        Log::info("🎉 [Lynk.id Sukses!] Kantor: {$office->name} berhasil diperpanjang (+{$durationDays} Hari). Berakhir pada: {$newExpiry->format('d M Y H:i')}");

        return response()->json([
            'status'     => 'success',
            'office'     => $office->name,
            'plan'       => $planType,
            'expired_at' => $newExpiry->format('Y-m-d H:i:s'),
        ]);
    }
}