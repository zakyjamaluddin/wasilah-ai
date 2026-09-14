<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan: {{ $order->invoice_number }} — Wasilah AI</title>
    
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                        },
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen py-10 px-4 flex items-center justify-center">

    <div class="max-w-md w-full bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-2xl space-y-6 text-center">
        
        {{-- Header Logo & Tagihan --}}
        <div class="space-y-1.5">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-teal-700 to-emerald-600 text-gold-400 font-black text-xl shadow-md">
                ⚡
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Tagihan Pendaftaran</h1>
            <p class="font-mono text-xs text-slate-400 font-bold bg-slate-100 px-3 py-1 rounded-full inline-block">
                {{ $order->invoice_number }}
            </p>
        </div>

        {{-- JIKA STATUS SUDAH LUNAS (PAID) --}}
        @if ($order->status === 'paid')
            <div class="p-6 bg-gradient-to-b from-emerald-50 to-teal-50 border border-emerald-200 text-emerald-900 rounded-3xl space-y-3 shadow-inner">
                <span class="text-4xl block">🎉</span>
                <h3 class="text-lg font-black text-emerald-800">Pembayaran Berhasil!</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Selamat, akun bisnis dan kantor <b>{{ $order->office?->name ?? $order->office_name }}</b> Anda telah aktif 100%.
                </p>
                <div class="pt-3">
                    <a 
                        href="/admin/{{ $order->office?->slug ?? 'wasilah-pusat' }}" 
                        class="inline-block w-full py-3.5 bg-gradient-to-r from-teal-700 to-emerald-600 hover:from-teal-800 hover:to-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-teal-700/20 hover:scale-105 transition duration-200"
                    >
                        Buka Dashboard Kantor Sekarang 🚀
                    </a>
                </div>
            </div>

        {{-- JIKA STATUS MASIH MENUNGGU PEMBAYARAN (PENDING) --}}
        @else
            {{-- Rincian Tagihan --}}
            <div class="p-5 bg-teal-50/60 border border-teal-200/70 rounded-2xl text-left space-y-2.5 text-xs">
                <div class="flex justify-between border-b border-teal-100/80 pb-2">
                    <span class="text-slate-500">Nama Pelanggan:</span>
                    <span class="font-bold text-slate-900">{{ $order->customer_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100/80 pb-2">
                    <span class="text-slate-500">Kantor Bisnis:</span>
                    <span class="font-bold text-slate-900">{{ $order->office_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100/80 pb-2">
                    <span class="text-slate-500">Paket Layanan:</span>
                    <span class="font-bold text-teal-800">{{ $order->plan_name }}</span>
                </div>
                <div class="flex justify-between pt-1 text-sm">
                    <span class="font-extrabold text-slate-700">Total Pembayaran:</span>
                    <span class="font-black text-emerald-700 text-base">
                        Rp {{ number_format($order->amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- Tombol Bayar Duitku --}}
            @if ($order->payment_url)
                <div class="space-y-3">
                    <a 
                        href="{{ $order->payment_url }}" 
                        class="block w-full py-4 rounded-2xl bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 font-black text-white text-sm shadow-xl shadow-teal-700/25 hover:shadow-teal-700/40 hover:scale-105 transition duration-200"
                    >
                        Bayar Sekarang (QRIS / VA / E-Wallet) 💳
                    </a>
                    
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Anda akan diarahkan ke halaman pembayaran aman <b>Duitku</b>. Pilih metode pembayaran favorit Anda (QRIS otomatis, Virtual Account semua bank, dll).
                    </p>
                </div>
            @else
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-xl">
                    Sedang memuat link pembayaran... Silakan refresh halaman ini.
                </div>
            @endif

            {{-- Auto-refresh setiap 5 detik untuk mendeteksi jika pembayaran sudah lunas di tab lain --}}
            <script>
                setTimeout(function() {
                    window.location.reload();
                }, 8000);
            </script>
        @endif

        {{-- Footer --}}
        <div class="pt-2 border-t border-slate-100">
            <a href="/" class="text-[11px] text-slate-400 hover:text-teal-700 font-semibold transition">
                ← Kembali ke Halaman Utama
            </a>
        </div>

    </div>

</body>
</html>