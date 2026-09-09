<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan: {{ $order->invoice_number }} — Wasilah AI</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Midtrans Snap JS --}}
    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen py-12 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xl space-y-6 text-center">

        <div class="space-y-1">
            <span class="text-2xl">⚡</span>
            <h1 class="text-xl font-black text-slate-900">Tagihan Pembayaran</h1>
            <p class="font-mono text-xs text-slate-400 font-bold">{{ $order->invoice_number }}</p>
        </div>

        @if ($order->status === 'paid')
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl space-y-2">
                <span class="text-3xl">🎉</span>
                <h3 class="text-base font-bold">Pembayaran Telah Diterima!</h3>
                <p class="text-xs">Akun Anda sudah aktif. Silakan masuk ke dashboard kantor Anda.</p>
                <div class="pt-2">
                    <a href="/admin" class="inline-block px-5 py-2.5 bg-emerald-600 text-white font-bold text-xs rounded-xl shadow">Masuk Dashboard 🚀</a>
                </div>
            </div>
        @else
            <div class="p-5 bg-teal-50/70 border border-teal-200/80 rounded-2xl text-left space-y-2 text-xs">
                <div class="flex justify-between border-b border-teal-100 pb-2">
                    <span class="text-slate-500">Pelanggan:</span>
                    <span class="font-bold text-slate-900">{{ $order->customer_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100 pb-2">
                    <span class="text-slate-500">Nama Kantor:</span>
                    <span class="font-bold text-slate-900">{{ $order->office_name }}</span>
                </div>
                <div class="flex justify-between border-b border-teal-100 pb-2">
                    <span class="text-slate-500">Paket:</span>
                    <span class="font-bold text-teal-700">{{ $order->plan_name }}</span>
                </div>
                <div class="flex justify-between pt-1 text-sm">
                    <span class="font-extrabold text-slate-700">Total Tagihan:</span>
                    <span class="font-black text-emerald-700 text-base">Rp {{ number_format($order->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <button id="pay-button" class="w-full py-4 rounded-2xl bg-gradient-to-r from-teal-700 to-emerald-600 font-black text-white text-sm shadow-xl shadow-teal-700/25 hover:scale-105 transition duration-200">
                Bayar Sekarang (QRIS / VA / E-Wallet) 💳
            </button>
            <p class="text-[11px] text-slate-400">Pilih metode pembayaran favorit Anda di popup Midtrans.</p>
        @endif

    </div>

    @if ($order->status === 'pending' && $order->snap_token)
        <script>
            document.getElementById('pay-button').onclick = function(){
                snap.pay('{{ $order->snap_token }}', {
                    onSuccess: function(result){
                        window.location.reload();
                    },
                    onPending: function(result){
                        window.location.reload();
                    },
                    onError: function(result){
                        alert("Pembayaran gagal. Silakan coba kembali.");
                    }
                });
            };
        </script>
    @endif
</body>
</html>
