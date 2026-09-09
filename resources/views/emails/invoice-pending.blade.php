<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
        .card { max-width: 550px; background: #ffffff; margin: 0 auto; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; }
        .header { text-align: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #0d9488, #059669); color: #ffffff !important; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2 style="color: #0f766e; margin: 0;">⚡ Wasilah.ai</h2>
            <p style="font-size: 12px; color: #64748b; margin: 5px 0 0 0;">Tagihan Pendaftaran Akun Baru</p>
        </div>

        <p>Halo <b>{{ $order->customer_name }}</b>,</p>
        <p>Terima kasih telah mendaftar di <b>Wasilah AI</b>. Tagihan pemesanan paket Anda telah berhasil dibuat:</p>

        <div style="background: #f0fdfa; padding: 15px; border-radius: 12px; border: 1px solid #ccfbf1; font-size: 13px;">
            <p style="margin: 3px 0;"><b>No. Invoice:</b> {{ $order->invoice_number }}</p>
            <p style="margin: 3px 0;"><b>Paket:</b> {{ $order->plan_name }}</p>
            <p style="margin: 3px 0;"><b>Nama Kantor/Bisnis:</b> {{ $order->office_name }}</p>
            <p style="margin: 3px 0; font-size: 15px; color: #0f766e;"><b>Total Bayar:</b> Rp {{ number_format($order->amount, 0, ',', '.') }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ route('checkout.invoice', ['invoice' => $order->invoice_number]) }}" class="btn">Bayar Tagihan Sekarang 💳</a>
        </div>

        <p style="font-size: 12px; color: #64748b; margin-top: 25px;">
            *Akun login dan kantor Anda akan <b>otomatis aktif</b> seketika setelah pembayaran terkonfirmasi.
        </p>

        <div class="footer">
            © {{ date('Y') }} Wasilah AI SaaS. Seluruh hak cipta dilindungi.
        </div>
    </div>
</body>
</html>
