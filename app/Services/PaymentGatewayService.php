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

    public function __construct()
    {
        $this->merchantCode = config('services.duitku.merchant_code', env('DUITKU_MERCHANT_CODE', ''));
        $this->apiKey = config('services.duitku.api_key', env('DUITKU_API_KEY', ''));
        $this->apiUrl = config('services.duitku.api_url');
    }

    /**
     * Buat Transaksi Pembayaran di Duitku (Popup / Redirect URL)
     */
    public function createTransaction(Order $order): ?string
    {
        $amount = (int) $order->amount;
        $orderId = $order->invoice_number;
        
        // Rumus Signature Duitku Request: MD5(merchantCode + orderId + amount + apiKey)
        $signature = md5($this->merchantCode . $orderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode'     => $this->merchantCode,
            'paymentAmount'    => $amount,
            'merchantOrderId'  => $orderId,
            'productDetails'   => "Paket Wasilah AI: " . $order->plan_name,
            'email'            => $order->customer_email,
            'customerVaName'   => $order->customer_name,
            'callbackUrl'      => url('/api/payment/webhook'),
            'returnUrl'        => route('checkout.invoice', ['invoice' => $order->invoice_number]),
            'signature'        => $signature,
            'expiryPeriod'     => 1440, // 24 Jam dalam menit
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

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

    /**
     * Verifikasi Tanda Tangan Webhook Duitku (MD5)
     */
    public function verifyWebhookSignature(string $merchantCode, string $amount, string $orderId, string $signature): bool
    {
        $calculated = md5($merchantCode . $amount . $orderId . $this->apiKey);
        return hash_equals($calculated, $signature);
    }
}