<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun Baru — Wasilah AI</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen py-10 px-4">
    <div class="max-w-4xl mx-auto space-y-6">

        <div class="text-center space-y-2">
            <a href="/" class="inline-flex items-center space-x-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-teal-700 to-emerald-600 text-amber-300 font-black text-base shadow">⚡</span>
                <span class="text-xl font-black text-slate-900">Wasilah<span class="text-teal-600">.ai</span></span>
            </a>
            <h1 class="text-2xl font-black text-slate-900">Langkah Terakhir: Aktivasi Bisnis Anda</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">

            {{-- Kolom Kiri: Form Registrasi --}}
            <div class="md:col-span-7 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">1. Informasi Akun & Kantor Bisnis</h2>

                @if ($errors->any())
                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
                        @foreach ($errors->all() as $error)
                            <p>• {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('checkout.process') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="plan_code" value="{{ $selectedPlan['code'] }}">

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Anda:</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: H. Ahmad Fauzi" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 focus:border-teal-600 focus:bg-white focus:outline-none" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Alamat Email:</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="ahmad@bisnisanda.com" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 focus:border-teal-600 focus:bg-white focus:outline-none" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Bisnis / Kantor Utama:</label>
                        <input type="text" name="office_name" value="{{ old('office_name') }}" placeholder="Contoh: Barokah Travel Umroh" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 focus:border-teal-600 focus:bg-white focus:outline-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Kata Sandi:</label>
                            <input type="password" name="password" placeholder="Minimal 6 karakter" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 focus:border-teal-600 focus:bg-white focus:outline-none" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi:</label>
                            <input type="password" name="password_confirmation" placeholder="Ketik ulang sandi" required class="w-full rounded-xl border border-slate-200 p-2.5 bg-slate-50 focus:border-teal-600 focus:bg-white focus:outline-none" />
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-teal-700 to-emerald-600 font-extrabold text-white text-sm shadow-lg shadow-teal-700/25 hover:scale-[1.02] transition duration-200">
                        Lanjut ke Pembayaran 💳
                    </button>
                </form>
            </div>

            {{-- Kolom Kanan: Ringkasan Paket --}}
            <div class="md:col-span-5 bg-gradient-to-b from-teal-900 to-emerald-900 text-white rounded-3xl p-6 shadow-xl space-y-4">
                <span class="text-[10px] font-bold tracking-widest uppercase text-amber-300 bg-white/10 px-2.5 py-0.5 rounded-full border border-white/15">Ringkasan Pesanan</span>
                <h3 class="text-xl font-black">{{ $selectedPlan['name'] }}</h3>
                <div class="text-2xl font-black text-amber-300">
                    Rp {{ number_format($selectedPlan['price'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-teal-100">/ bulan</span>
                </div>
                <p class="text-xs text-teal-100/90 leading-relaxed border-t border-white/10 pt-3">
                    {{ $selectedPlan['desc'] }}
                </p>
                <div class="text-[11px] text-teal-200/80 pt-2 space-y-1.5 border-t border-white/10">
                    <p>✓ Akses Penuh Sistem Omnichannel</p>
                    <p>✓ AI Gemini Vision Paham Gambar</p>
                    <p>✓ Akun Aktif Otomatis Seketika</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
