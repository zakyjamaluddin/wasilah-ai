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
            <h2 style="color: #059669; margin: 0;">🎉 Pembayaran Berhasil!</h2>
            <p style="font-size: 12px; color: #64748b; margin: 5px 0 0 0;">Selamat Datang di Wasilah AI</p>
        </div>

        <p>Halo <b>{{ $order->customer_name }}</b>,</p>
        <p>Pembayaran tagihan <b>{{ $order->invoice_number }}</b> sebesar <b>Rp {{ number_format($order->amount, 0, ',', '.') }}</b> telah kami terima. Akun bisnis Anda telah <b>aktif 100%</b>.</p>

        <div style="background: #f0fdf4; padding: 15px; border-radius: 12px; border: 1px solid #bbf7d0; font-size: 13px;">
            <p style="margin: 3px 0;"><b>🏢 Nama Kantor:</b> {{ $order->office->name }}</p>
            <p style="margin: 3px 0;"><b>📧 Email Login:</b> {{ $order->customer_email }}</p>
            <p style="margin: 3px 0;"><b>🔑 Kata Sandi:</b> (Sesuai yang Anda daftarkan)</p>
            <p style="margin: 3px 0;"><b>📦 Paket:</b> {{ $order->plan_name }} (Aktif 30 Hari)</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="btn">Masuk ke Dashboard Kantor 🚀</a>
        </div>

        <p style="font-size: 12px; color: #64748b; margin-top: 25px;">
            Anda bisa langsung menghubungkan nomor WhatsApp, akun Facebook, Instagram, serta mengatur asisten AI untuk bisnis Anda.
        </p>

        <div class="footer">
            © {{ date('Y') }} Wasilah AI SaaS. Seluruh hak cipta dilindungi.
        </div>
    </div>
</body>
</html>
