<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan: {{ $order->invoice_number }} — Wasilah AI</title>
    
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen py-10 px-4 flex items-center justify-center" x-data="{ selectedMethod: 'SP' }">

    <div class="max-w-lg w-full bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-2xl space-y-6 text-center">
        
        {{-- Header Logo & Tagihan --}}
        <div class="space-y-1.5">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-teal-700 to-emerald-600 text-amber-300 font-black text-xl shadow-md">
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
                <h3 class="text-lg font-black text-emerald-800">Pembayaran Berhasil Diterima!</h3>
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
            <div class="p-4 bg-teal-50/60 border border-teal-200/70 rounded-2xl text-left space-y-2 text-xs">
                <div class="flex justify-between border-b border-teal-100/80 pb-1.5">
                    <span class="text-slate-500">Nama Pelanggan:</span>
                    <span class="font-bold text-slate-900">{{ $order->customer_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100/80 pb-1.5">
                    <span class="text-slate-500">Kantor Bisnis:</span>
                    <span class="font-bold text-slate-900">{{ $order->office_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100/80 pb-1.5">
                    <span class="text-slate-500">Paket Layanan:</span>
                    <span class="font-bold text-teal-800">{{ $order->plan_name }}</span>
                </div>
                <div class="flex justify-between pt-1 text-sm">
                    <span class="font-extrabold text-slate-700">Total Tagihan:</span>
                    <span class="font-black text-emerald-700 text-base">
                        Rp {{ number_format($order->amount, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- FORM PILIHAN METODE PEMBAYARAN DINAMIS --}}
            <form action="{{ route('checkout.pay', ['invoice' => $order->invoice_number]) }}" method="POST" class="space-y-4 text-left">
                @csrf
                <input type="hidden" name="payment_method" :value="selectedMethod">

                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-2">Pilih Metode Pembayaran Favorit:</label>
                    
                    <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                        @foreach ($paymentMethods as $method)
                            <div 
                                @click="selectedMethod = '{{ $method['paymentMethod'] }}'"
                                class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition"
                                :class="selectedMethod === '{{ $method['paymentMethod'] }}' ? 'border-teal-600 bg-teal-50/70 ring-2 ring-teal-500/20 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'"
                            >
                                <div class="flex items-center space-x-3">
                                    @if (!empty($method['paymentImage']))
                                        <img src="{{ $method['paymentImage'] }}" alt="{{ $method['paymentName'] }}" class="h-6 w-auto object-contain flex-shrink-0" />
                                    @else
                                        <span class="text-lg">💳</span>
                                    @endif
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900">{{ $method['paymentName'] }}</h4>
                                        <span class="text-[10px] text-slate-400">Biaya Admin: Rp {{ number_format($method['totalFee'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="flex h-5 w-5 items-center justify-center rounded-full border" :class="selectedMethod === '{{ $method['paymentMethod'] }}' ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-300 bg-white'">
                                    <span class="text-[10px]" x-show="selectedMethod === '{{ $method['paymentMethod'] }}'">✓</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 font-black text-white text-sm shadow-xl shadow-teal-700/25 hover:shadow-teal-700/40 hover:scale-105 transition duration-200 text-center"
                >
                    Bayar Sekarang 💳
                </button>
            </form>

            {{-- Auto-refresh setiap 8 detik jika bayar di tab lain --}}
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