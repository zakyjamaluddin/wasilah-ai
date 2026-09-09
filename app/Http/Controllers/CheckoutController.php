<?php

namespace App\Http\Controllers;

use App\Mail\InvoicePendingMail;
use App\Models\Order;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * 1. Halaman Formulir Registrasi & Checkout
     */
    public function show(Request $request)
    {
        $planCode = $request->query('plan', 'pro');

        $plans = [
            'starter' => [
                'code' => 'starter',
                'name' => 'Starter Bisnis',
                'price' => 299000,
                'desc' => '1 Kantor Cabang, 1 WA + FB + IG, AI Gemini Vision',
            ],
            'pro' => [
                'code' => 'pro',
                'name' => 'Pro Enterprise (Terlaris)',
                'price' => 599000,
                'desc' => 'Hingga 5 Cabang, Multi-Step Follow-Up Drip, Website Scraper, Broadcast Anti-Ban',
            ],
        ];

        $selectedPlan = $plans[$planCode] ?? $plans['pro'];

        return view('checkout.register', compact('selectedPlan'));
    }

    /**
     * 2. Proses Pembuatan Tagihan & Invoice
     */
    public function process(Request $request, PaymentGatewayService $paymentGateway)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'office_name' => 'required|string|max:255',
            'plan_code' => 'required|in:starter,pro',
        ], [
            'email.unique' => 'Alamat email ini sudah terdaftar di sistem. Silakan gunakan email lain atau login.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $prices = [
            'starter' => ['name' => 'Starter Bisnis', 'amount' => 299000],
            'pro' => ['name' => 'Pro Enterprise', 'amount' => 599000],
        ];

        $planInfo = $prices[$validated['plan_code']];
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        // Buat data order (Akun BELUM DIBUAT sampai pembayaran berstatus PAID)
        $order = Order::create([
            'invoice_number' => $invoiceNumber,
            'plan_code' => $validated['plan_code'],
            'plan_name' => $planInfo['name'],
            'amount' => $planInfo['amount'],
            'status' => 'pending',
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'],
            'customer_password' => Hash::make($validated['password']), // Password di-hash aman
            'office_name' => $validated['office_name'],
        ]);

        // Buat Snap Token di Midtrans
        $paymentGateway->createSnapTransaction($order);

        // 📧 Kirim Email Tagihan ke Klien
        try {
            Mail::to($order->customer_email)->send(new InvoicePendingMail($order));
        } catch (\Exception $e) {}

        return redirect()->route('checkout.invoice', ['invoice' => $order->invoice_number]);
    }

    /**
     * 3. Halaman Pembayaran Tagihan (Invoice Screen & Snap Popup)
     */
    public function invoice(string $invoiceNumber)
    {
        $order = Order::where('invoice_number', $invoiceNumber)->firstOrFail();
        return view('checkout.invoice', compact('order'));
    }
}
