<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected string $merchantCode;
    protected string $apiKey;
    protected string $apiUrl;
    protected string $paymentMethodUrl;

    public function __construct()
    {
        $this->merchantCode = (string) (config('services.duitku.merchant_code') ?: env('DUITKU_MERCHANT_CODE', ''));
        $this->apiKey = (string) (config('services.duitku.api_key') ?: env('DUITKU_API_KEY', ''));

        $isProduction = config('services.duitku.is_production', env('DUITKU_IS_PRODUCTION', false));

        $this->apiUrl = $isProduction
            ? 'https://api-prod.duitku.com/api/merchant/v2/inquiry'
            : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

        $this->paymentMethodUrl = $isProduction
            ? 'https://api-prod.duitku.com/api/merchant/paymentmethod/getpaymentmethod'
            : 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
    }

    /**
     * 1. Ambil Seluruh Daftar Metode Pembayaran yang Aktif di Akun Duitku Anda
     */
    public function getAvailablePaymentMethods(int $amount): array
    {
        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $this->merchantCode . $amount . $datetime . $this->apiKey);

        $payload = [
            'merchantcode' => $this->merchantCode,
            'amount'       => $amount,
            'datetime'     => $datetime,
            'signature'    => $signature,
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(15)
                ->post($this->paymentMethodUrl, $payload);

            if ($response->successful() && $response->json('responseCode') === '00') {
                return $response->json('paymentFee') ?? [];
            }
            Log::error("[Duitku GetPaymentMethods Error] " . $response->body());
        } catch (\Exception $e) {
            Log::error("[Duitku GetPaymentMethods Exception] " . $e->getMessage());
        }

        // Fallback Standar jika koneksi API getPaymentMethod sedang lambat
        return [
            ['paymentMethod' => 'SP', 'paymentName' => 'QRIS (BCA, Mandiri, Gopay, OVO, Dana)', 'paymentImage' => 'https://images.duitku.com/channels/qris.png', 'totalFee' => 0],
            ['paymentMethod' => 'BC', 'paymentName' => 'BCA Virtual Account', 'paymentImage' => 'https://images.duitku.com/channels/bca.png', 'totalFee' => 3000],
            ['paymentMethod' => 'M2', 'paymentName' => 'Mandiri Virtual Account', 'paymentImage' => 'https://images.duitku.com/channels/mandiri.png', 'totalFee' => 3000],
            ['paymentMethod' => 'BR', 'paymentName' => 'BRI Virtual Account', 'paymentImage' => 'https://images.duitku.com/channels/bri.png', 'totalFee' => 3000],
            ['paymentMethod' => 'I1', 'paymentName' => 'BNI Virtual Account', 'paymentImage' => 'https://images.duitku.com/channels/bni.png', 'totalFee' => 3000],
        ];
    }

    /**
     * 2. Buat Transaksi Sesuai Metode Pembayaran yang Dipilih Customer
     */
    public function createTransaction(Order $order, string $paymentMethod = 'SP'): ?string
    {
        $amount = (int) $order->amount;
        $orderId = $order->invoice_number;
        $signature = md5($this->merchantCode . $orderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode'     => $this->merchantCode,
            'paymentAmount'    => $amount,
            'paymentMethod'    => $paymentMethod, // 🔥 Sesuai Pilihan Customer!
            'merchantOrderId'  => $orderId,
            'productDetails'   => "Paket Wasilah AI: " . $order->plan_name,
            'email'            => $order->customer_email,
            'customerVaName'   => $order->customer_name,
            'callbackUrl'      => url('/api/payment/webhook'),
            'returnUrl'        => route('checkout.invoice', ['invoice' => $order->invoice_number]),
            'signature'        => $signature,
            'expiryPeriod'     => 1440,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(20)->post($this->apiUrl, $payload);

            if ($response->successful() && $response->json('statusCode') === '00') {
                $paymentUrl = $response->json('paymentUrl');
                $reference = $response->json('reference');

                $order->update([
                    'payment_url' => $paymentUrl,
                    'snap_token'  => $reference,
                ]);

                return $paymentUrl;
            } else {
                Log::error("[Duitku Create Error] " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("[Duitku Exception] " . $e->getMessage());
            return null;
        }
    }

    public function verifyWebhookSignature(string $merchantCode, string $amount, string $orderId, string $signature): bool
    {
        $calculated = md5($merchantCode . $amount . $orderId . $this->apiKey);
        return hash_equals($calculated, $signature);
    }
}