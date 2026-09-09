<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationSuccessMail;
use App\Models\Office;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, PaymentGatewayService $paymentGateway)
    {
        $merchantCode = $request->input('merchantCode') ?? '';
        $amount = $request->input('amount') ?? '';
        $merchantOrderId = $request->input('merchantOrderId') ?? '';
        $signature = $request->input('signature') ?? '';
        $resultCode = $request->input('resultCode') ?? '';

        Log::info("=== [DUITKU WEBHOOK MASUK] === Order ID: {$merchantOrderId}, Result Code: {$resultCode}");

        if (!$merchantOrderId || !$signature) {
            return response('BAD_REQUEST', 400);
        }

        // 🛡️ 1. Verifikasi Keaslian Tanda Tangan Keamanan Duitku
        if (!$paymentGateway->verifyWebhookSignature($merchantCode, $amount, $merchantOrderId, $signature)) {
            Log::error("[Duitku Signature Invalid] Order: {$merchantOrderId}");
            return response('INVALID_SIGNATURE', 403);
        }

        $order = Order::where('invoice_number', $merchantOrderId)->first();
        if (!$order) {
            return response('ORDER_NOT_FOUND', 404);
        }

        // 🎯 2. JIKA RESULT CODE = '00' (PEMBAYARAN SUKSES / LUNAS) -> AKTIFKAN AKUN!
        if ($resultCode === '00') {
            
            if ($order->status === 'paid') {
                return response('ALREADY_PAID', 200);
            }

            DB::transaction(function () use ($order) {
                // A. Buat Kantor Baru Klien
                $slug = Str::slug($order->office_name);
                if (Office::where('slug', $slug)->exists()) {
                    $slug .= '-' . Str::random(3);
                }

                $office = Office::create([
                    'name' => $order->office_name,
                    'slug' => $slug,
                    'is_active' => true,
                ]);

                // B. Buat Akun User Baru Klien
                $user = User::create([
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'password' => $order->customer_password,
                    'email_verified_at' => now(),
                ]);

                // C. Hubungkan User ke Kantor Miliknya
                $user->offices()->attach($office->id);

                // D. Pasangkan Role Admin dengan Team Context
                setPermissionsTeamId($office->id);
                $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
                $user->assignRole($adminRole);

                // E. Catat Langganan Aktif 30 Hari
                Subscription::create([
                    'office_id' => $office->id,
                    'user_id' => $user->id,
                    'plan_code' => $order->plan_code,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays(30),
                    'status' => 'active',
                ]);

                // F. Update Status Order menjadi 'paid'
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                ]);

                // 📧 G. Kirim Email Aktivasi Sukses
                $loginUrl = url("/admin/{$office->slug}");
                try {
                    Mail::to($order->customer_email)->send(new RegistrationSuccessMail($order, $loginUrl));
                } catch (\Exception $e) {
                    Log::error("[Send Activation Mail Error] " . $e->getMessage());
                }

                Log::info("🎉 [AKTIVASI SUKSES DUITKU] User {$user->email} & Kantor {$office->name} Berhasil Dibuat!");
            });

            return response('OK', 200);
        }

        // Jika Transaksi Gagal / Batal
        if ($resultCode === '01') {
            $order->update(['status' => 'failed']);
        }

        return response('OK', 200);
    }
}