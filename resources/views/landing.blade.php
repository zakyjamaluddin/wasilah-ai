<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wasilah AI - Platform Omnichannel CRM & AI Chatbot Otomatis #1</title>
    <meta name="description" content="Tingkatkan closing penjualan dan otomatisasi layanan pelanggan lintas WhatsApp, Instagram, dan Facebook dengan kecerdasan buatan Gemini AI.">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

     <link rel="icon" href="/favicon.svg" type="image/svg+xml">

     <!-- Meta Standar SEO -->
    <meta name="description" content="Otomatisasi layanan pelanggan & percepat closing penjualan lintas WhatsApp, Instagram, dan Facebook dengan kecerdasan buatan Gemini AI.">
    <meta name="keywords" content="omnichannel crm, ai chatbot whatsapp, follow up otomatis umroh, crm indonesia, bot wa cerdas, wasilah ai">
    <meta name="author" content="Wasilah AI">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0f766e">

    <!-- 🔥 1. OPEN GRAPH / FACEBOOK / WHATSAPP / TELEGRAM / LINKEDIN -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Wasilah.ai">
    <meta property="og:title" content="Wasilah AI — Platform Omnichannel AI Chatbot & CRM Penjualan #1">
    <meta property="og:description" content="Tingkatkan closing penjualan dan otomatisasi chat 24 jam lintas WhatsApp, Instagram, dan Facebook dengan AI Gemini Vision. Coba sekarang!">
    <!-- Gambar Banner Preview (Ukuran Standar 1200 x 630 px) -->
    <meta property="og:image" content="hero.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Wasilah AI Omnichannel CRM Workspace">
    <meta property="og:locale" content="id_ID">

    <!-- 🔥 2. TWITTER / X CARD (BANNER BESAR) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url('/') }}">
    <meta name="twitter:title" content="Wasilah AI — Platform Omnichannel AI Chatbot & CRM Penjualan #1">
    <meta name="twitter:description" content="Otomatisasi layanan pelanggan & percepat closing penjualan lintas WhatsApp, Instagram, dan Facebook dengan kecerdasan buatan Gemini AI.">
    <meta name="twitter:image" content="hero.jpg">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white font-sans text-slate-800 antialiased overflow-x-hidden selection:bg-teal-500 selection:text-white" x-data="{ mobileMenu: false }">

    {{-- ========================================================================= --}}
    {{-- 1. STICKY NAVBAR (GLASSMORPHISM)                                          --}}
    {{-- ========================================================================= --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/85 backdrop-blur-md border-b border-slate-100 transition duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">

            {{-- Brand Logo --}}
            <a href="/" class="flex items-center space-x-2.5 group">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-teal-700 to-emerald-500 text-gold-300 font-black text-lg shadow-md shadow-teal-700/20 group-hover:scale-105 transition">
                    ⚡
                </span>
                <span class="text-xl font-black tracking-tight text-slate-900">
                    Wasilah<span class="text-teal-600">.ai</span>
                </span>
            </a>

            {{-- Desktop Navigation Links --}}
            <div class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                <a href="#fitur" class="hover:text-teal-600 transition">Fitur Utama</a>
                <a href="#omnichannel" class="hover:text-teal-600 transition">Omnichannel</a>
                <a href="#followup" class="hover:text-teal-600 transition">Drip Follow-Up</a>
                <a href="#harga" class="hover:text-teal-600 transition">Paket Harga</a>
                <a href="#faq" class="hover:text-teal-600 transition">FAQ</a>
            </div>

            {{-- CTA Button --}}
            <div class="hidden md:flex items-center space-x-4">
                <a href="/admin" class="text-sm font-bold text-slate-700 hover:text-teal-700 transition">
                    Masuk
                </a>
                <a href="#harga" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-700 to-emerald-600 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-teal-700/20 hover:shadow-teal-700/30 hover:scale-105 transition">
                    <span>Mulai Sekarang</span>
                    <span class="text-gold-300">→</span>
                </a>
            </div>

            {{-- Mobile Menu Button --}}
            <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        {{-- Mobile Drawer Menu --}}
        <div x-show="mobileMenu" @click.away="mobileMenu = false" class="md:hidden border-b border-slate-100 bg-white px-4 pt-2 pb-6 space-y-3">
            <a href="#fitur" @click="mobileMenu = false" class="block py-2 text-sm font-semibold text-slate-700">Fitur Utama</a>
            <a href="#omnichannel" @click="mobileMenu = false" class="block py-2 text-sm font-semibold text-slate-700">Omnichannel</a>
            <a href="#followup" @click="mobileMenu = false" class="block py-2 text-sm font-semibold text-slate-700">Drip Follow-Up</a>
            <a href="#harga" @click="mobileMenu = false" class="block py-2 text-sm font-semibold text-slate-700">Paket Harga</a>
            <a href="/admin" class="block w-full text-center py-2.5 rounded-xl bg-gradient-to-r from-teal-700 to-emerald-600 font-bold text-white shadow-md">
                Buka Dashboard
            </a>
        </div>
    </nav>

    {{-- ========================================================================= --}}
    {{-- 2. HERO SECTION DENGAN AKSEN MEWAH & BADGE EMAS                           --}}
    {{-- ========================================================================= --}}
    <section class="relative pt-32 pb-20 md:pt-40 md:pb-28 overflow-hidden bg-gradient-to-b from-teal-50/40 via-white to-white">

        {{-- Background Glowing Blobs --}}
        <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-teal-200/40 to-emerald-100/30 blur-3xl -z-10 rounded-full"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">

            {{-- Badge Top Pill --}}
            <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 border border-teal-200/80 px-4 py-1.5 shadow-sm">
                <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-teal-900">Platform AI Omnichannel CRM & Automasi Penjualan #1</span>
            </div>

            {{-- Main Headline --}}
            <h1 class="text-3xl sm:text-5xl md:text-6xl font-black tracking-tight text-slate-900 max-w-4xl mx-auto leading-tight md:leading-[1.15]">
                Otomatisasi Chat & <span class="bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 bg-clip-text text-transparent">Percepat Closing Penjualan</span> Lintas WhatsApp, IG, & FB
            </h1>

            {{-- Sub-headline --}}
            <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Asisten AI cerdas <b>Support DM & Comment Reply</b> berbasis Gemini Vision yang paham foto struk/produk, otomatis follow-up berbulan-bulan (siap untuk travel umroh & properti), dan menyatukan seluruh percakapan dalam 1 meja kerja.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                <a href="#harga" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 px-8 py-4 text-base font-extrabold text-white shadow-xl shadow-teal-700/25 hover:shadow-teal-700/40 hover:scale-105 transition duration-200">
                    <span>Coba Sekarang</span>
                    <span class="text-gold-300">🚀</span>
                </a>
                <a href="#fitur" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 shadow-sm hover:border-teal-500 hover:text-teal-700 hover:bg-slate-50 transition">
                    <span>Lihat Fitur Lengkap Kami</span>
                </a>
            </div>

            {{-- Social Proof Badges --}}
            <div class="pt-8 flex flex-wrap items-center justify-center gap-8 text-xs font-semibold text-slate-400">
                <div class="flex items-center gap-1.5"><span class="text-emerald-600 text-base">✓</span> Multi-Cabang Terisolasi</div>
                <div class="flex items-center gap-1.5"><span class="text-emerald-600 text-base">✓</span> Anti-Banned Broadcast</div>
                <div class="flex items-center gap-1.5"><span class="text-emerald-600 text-base">✓</span> AI Paham Foto & Gambar</div>
                <div class="flex items-center gap-1.5"><span class="text-emerald-600 text-base">✓</span> Knowledge dinamis dari URL</div>
            </div>

            {{-- Hero Showcase Mockup --}}
            <div class="pt-10 max-w-5xl mx-auto">
                <div class="relative rounded-3xl p-3 bg-gradient-to-b from-slate-200 to-slate-100 shadow-2xl border border-slate-200">
                    <div class="rounded-2xl overflow-hidden bg-white shadow-inner border border-slate-200/80">
                        <img src="hero.jpg" alt="Wasilah CRM Workspace Mockup" class="w-full h-auto object-cover max-h-[500px]" />
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 3. FITUR UTAMA: 4 PILAR KEKUATAN WASILAH                                  --}}
    {{-- ========================================================================= --}}
    <section id="fitur" class="py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center space-y-3 mb-16">
                <span class="text-xs font-extrabold uppercase tracking-widest text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-200">Fitur Unggulan</span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Segala yang Anda Butuhkan untuk Skalasi Penjualan</h2>
                <p class="text-sm sm:text-base text-slate-500 max-w-xl mx-auto">Tinggalkan cara manual membalas chat satu per satu. Biarkan sistem bekerja otomatis 24 jam nonstop.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                {{-- Card 1: Omnichannel Workspace --}}
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50/50 p-6 space-y-4 hover:border-teal-500 hover:shadow-xl hover:shadow-teal-700/5 hover:-translate-y-1 transition duration-300">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-teal-700 to-emerald-500 text-white flex items-center justify-center text-xl shadow-md">
                        💬
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">OmniChat 3-Platform</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Satu meja kerja terpusat untuk WhatsApp, Instagram DM/Komen, dan Facebook Messenger tanpa perlu buka banyak aplikasi.
                    </p>
                </div>

                {{-- Card 2: AI Gemini Vision --}}
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50/50 p-6 space-y-4 hover:border-amber-400 hover:shadow-xl hover:shadow-amber-500/5 hover:-translate-y-1 transition duration-300">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-gold-500 text-white flex items-center justify-center text-xl shadow-md">
                        🧠
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Otak AI Paham Gambar</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        AI membaca foto produk atau bukti transfer yang dikirim customer, serta otomatis menganalisis kebutuhan prospek (*Hot Leads*).
                    </p>
                </div>

                {{-- Card 3: Multi-Step Follow-Up --}}
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50/50 p-6 space-y-4 hover:border-teal-500 hover:shadow-xl hover:shadow-teal-700/5 hover:-translate-y-1 transition duration-300">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-teal-800 to-teal-600 text-white flex items-center justify-center text-xl shadow-md">
                        🎯
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Drip Nurturing Berseri</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Follow-up otomatis berbulan-bulan (Step 1 s/d N tanpa batas). Sangat cocok untuk biro travel umroh & penjualan properti.
                    </p>
                </div>

                {{-- Card 4: Broadcast Anti-Banned --}}
                <div class="rounded-3xl border border-slate-200/80 bg-slate-50/50 p-6 space-y-4 hover:border-emerald-500 hover:shadow-xl hover:shadow-emerald-700/5 hover:-translate-y-1 transition duration-300">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-emerald-500 text-white flex items-center justify-center text-xl shadow-md">
                        📢
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Broadcast Anti-Ban</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Kirim ribuan pesan promosi dengan jeda waktu acak dan lampiran file/video.
                    </p>
                </div>

            </div>

        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 4. DEEP DIVE: SOLUSI KHUSUS TRAVEL UMROH & HIGH-TICKET SALES              --}}
    {{-- ========================================================================= --}}
    <section id="followup" class="py-24 bg-gradient-to-b from-slate-50 to-teal-50/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                {{-- Left Text & Process --}}
                <div class="space-y-6">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-amber-700 bg-amber-100 px-3 py-1 rounded-full">Solusi Siklus Penjualan Panjang</span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 leading-tight">
                        Jangan Biarkan Calon Customer & Prospek Mahal Anda "Ghosting"
                    </h2>
                    <p class="text-sm text-slate-600 leading-relaxed">
                        Menjual paket dengan big deal seperti Properti dan Umrah membutuhkan pendekatan berminggu-minggu. Wasilah AI memastikan calon customer Anda disapa secara berkala dengan materi katalog, rincian harga, dan info sisa kuota secara otomatis.
                    </p>


                </div>

                {{-- Right Image --}}
                <div class="relative">
                    <div class="space-y-3 md:pt-24">
                        <div class="flex items-start space-x-3 p-3 rounded-2xl bg-white border border-slate-200 shadow-sm">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-bold text-xs flex-shrink-0">1</span>
                            <p class="text-xs text-slate-700"><b>Hari ke-1:</b> Kirim PDF Brosur & Rincian Fasilitas.</p>
                        </div>
                        <div class="flex items-start space-x-3 p-3 rounded-2xl bg-white border border-slate-200 shadow-sm">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-bold text-xs flex-shrink-0">2</span>
                            <p class="text-xs text-slate-700"><b>Hari ke-3:</b> Kirim Video Kamar.</p>
                        </div>
                        <div class="flex items-start space-x-3 p-3 rounded-2xl bg-white border border-slate-200 shadow-sm">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 text-amber-800 font-bold text-xs flex-shrink-0">3</span>
                            <p class="text-xs text-slate-700"><b>Hari ke-7:</b> AI Gemini menyapa hangat dan menanyakan rencana kebutuhan.</p>
                        </div>
                        <div class="flex items-start space-x-3 p-3 rounded-2xl bg-white border border-emerald-300 bg-emerald-50/50 shadow-sm">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-600 text-white font-bold text-xs flex-shrink-0">✓</span>
                            <p class="text-xs text-emerald-900 font-bold">Otomatis Berhenti Saat Jamaah Bayar DP (Status Won/Closing 🎉).</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 5. TABEL PAKET HARGA (PRICING TIERS DENGAN AKSEN GOLD)                    --}}
    {{-- ========================================================================= --}}
    <section id="harga" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center space-y-3 mb-16">
                <span class="text-xs font-extrabold uppercase tracking-widest text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-200">Investasi Terbaik</span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Pilih Paket Sesuai Skala Bisnis Anda</h2>
                <p class="text-sm text-slate-500 max-w-md mx-auto">Tanpa biaya tersembunyi. Aktif seketika setelah aktivasi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">

                {{-- Tier 1: Starter --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-8 flex flex-col justify-between space-y-6 hover:shadow-xl transition">
                    <div class="space-y-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paket Single Cabang</span>
                        <h3 class="text-2xl font-black text-slate-900">Starter Bisnis</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 37.000</span>
                            <span class="text-xs text-slate-500 font-semibold">/ bulan</span>
                        </div>
                        <p class="text-xs text-slate-500">Cocok untuk toko online dan bisnis yang baru memulai otomasi chat.</p>

                        <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> 1 Kantor Cabang</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> 1 WhatsApp + 1 FB + 1 IG</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> AI Chatbot Gemini Teks & Gambar</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> OmniChat Workspace Live CRM</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Maksimal 2.000 Kontak Leads</li>
                        </ul>
                    </div>
                    <a href="http://lynk.id/zakyjamal/zlgw19zge6p9/checkout" target="_blank" class="w-full text-center py-3 rounded-xl border-2 border-slate-200 font-bold text-slate-700 hover:border-teal-600 hover:text-teal-700 transition text-xs">
                        Pilih Paket Starter
                    </a>
                </div>

                {{-- Tier 2: PRO (Featured - Luxury Gold & Emerald) --}}
                <div class="rounded-3xl border-2 border-teal-600 bg-gradient-to-b from-teal-50/60 to-white p-8 flex flex-col justify-between space-y-6 shadow-2xl relative">
                    <span class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-amber-500 to-gold-500 px-4 py-1 text-[10px] font-black tracking-widest uppercase text-slate-900 shadow-md">
                        ⭐ Rekomendasi Terbaik
                    </span>
                    <div class="space-y-4">
                        <span class="text-xs font-bold text-teal-800 uppercase tracking-wider">Multi-Cabang & Agensi</span>
                        <h3 class="text-2xl font-black text-slate-900">Pro Enterprise</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 185.000</span>
                            <span class="text-xs text-slate-500 font-semibold">/ bulan</span>
                        </div>
                        <p class="text-xs text-slate-600">Pilihan utama Bisnis Properti, dan Bisnis Multi-Cabang.</p>

                        <ul class="space-y-2.5 text-xs text-slate-800 pt-4 border-t border-teal-200/60 font-medium">
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> <b>Hingga 5 Kantor Cabang</b></li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> 5 WhatsApp + 5 FB + 5 IG</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> <b>Multi-Step Drip Follow-Up Tanpa Batas</b></li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Dynamic HTML Website Scraper</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Broadcast WhatsApp Anti-Banned</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Unlimited Kontak Leads</li>
                        </ul>
                    </div>
                    <a href="http://lynk.id/zakyjamal/n6pkx389k914/checkout" target="_blank" class="w-full text-center py-3.5 rounded-xl bg-gradient-to-r from-teal-700 to-emerald-600 font-extrabold text-white shadow-lg shadow-teal-700/25 hover:shadow-teal-700/40 hover:scale-105 transition text-xs">
                        Ambil Paket Pro Sekarang 🚀
                    </a>
                </div>

                {{-- Tier 3: Custom Agency --}}
                <div class="rounded-3xl border border-slate-200 bg-white p-8 flex flex-col justify-between space-y-6 hover:shadow-xl transition">
                    <div class="space-y-4">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Custom Skala Besar</span>
                        <h3 class="text-2xl font-black text-slate-900">Custom White-Label</h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900">Rp 25.000</span>
                            <span class="text-xs text-slate-500 font-semibold">/ bulan per kantor cabang</span>
                        </div>
                        <p class="text-xs text-slate-500">Minimal 10 Kantor Cabang</p>

                        <ul class="space-y-2.5 text-xs text-slate-700 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Unlimited Kantor Cabang</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Unlimited WA, FB dan Instagram</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> <b>Multi-Step Drip Follow-Up Tanpa Batas</b></li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Dynamic HTML Website Scraper</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Broadcast WhatsApp Anti-Banned</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-600 font-bold">✓</span> Unlimited Kontak Leads</li>
                        </ul>
                    </div>
                    <a href="https://lynk.id/zakyjamal/mnqmgzmo6rzx/checkout" target="_blank" class="w-full text-center py-3 rounded-xl border-2 border-slate-200 font-bold text-slate-700 hover:border-teal-600 hover:text-teal-700 transition text-xs">
                        Konsultasi Khusus
                    </a>
                </div>

            </div>

        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 6. FAQ ACCORDION (TANYA JAWAB)                                             --}}
    {{-- ========================================================================= --}}
    <section id="faq" class="py-24 bg-slate-50 border-t border-slate-200/60" x-data="{ activeFaq: null }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center space-y-3 mb-16">
                <span class="text-xs font-extrabold uppercase tracking-widest text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-200">FAQ</span>
                <h2 class="text-3xl font-black text-slate-900">Pertanyaan yang Sering Diajukan</h2>
            </div>

            <div class="space-y-4">

                {{-- FAQ 1 --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <button @click="activeFaq = (activeFaq === 1 ? null : 1)" class="flex w-full items-center justify-between text-left text-sm font-bold text-slate-900">
                        <span>Apakah nomor WhatsApp saya aman dari pemblokiran (Banned)?</span>
                        <span class="text-teal-600 font-black text-lg" x-text="activeFaq === 1 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 1" x-collapse class="pt-3 text-xs text-slate-600 leading-relaxed">
                        Sangat aman. Wasilah dilengkapi dengan mesin <b>Anti-Ban Smart Jitter</b> yang memberi jeda waktu acak (5–12 detik per pesan) dan memanggil nama penerima secara personal , sehingga algoritma WhatsApp mendeteksinya sebagai manusia asli yang sedang mengetik.
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <button @click="activeFaq = (activeFaq === 2 ? null : 2)" class="flex w-full items-center justify-between text-left text-sm font-bold text-slate-900">
                        <span>Bagaimana cara AI mempelajari produk bisnis saya?</span>
                        <span class="text-teal-600 font-black text-lg" x-text="activeFaq === 2 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 2" x-collapse class="pt-3 text-xs text-slate-600 leading-relaxed">
                        Anda cukup memasukkan teks SOP/harga di menu <b>Knowledge Base</b>, ATAU cukup masukkan link website/katalog toko Anda. Sistem Wasilah akan otomatis men-scrape dan membaca seluruh isi website tersebut secara mandiri!
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <button @click="activeFaq = (activeFaq === 3 ? null : 3)" class="flex w-full items-center justify-between text-left text-sm font-bold text-slate-900">
                        <span>Apakah bisa digunakan untuk banyak kantor cabang yang berbeda?</span>
                        <span class="text-teal-600 font-black text-lg" x-text="activeFaq === 3 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 3" x-collapse class="pt-3 text-xs text-slate-600 leading-relaxed">
                        Ya, tentu saja! Wasilah dibangun dengan arsitektur <b>Multi-Tenant Multi-Branch</b>. Anda bisa mengelola puluhan cabang toko/travel dengan nomor WA, akun FB/IG, dan data leads yang terisolasi aman per cabang.
                    </div>
                </div>

            </div>

        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 7. BOTTOM BANNER CTA (SIAP MEROKET)                                       --}}
    {{-- ========================================================================= --}}
    <section class="py-20 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white relative overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 text-center space-y-6 relative z-10">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight">
                Siap Melipatgandakan Closing Penjualan Anda Hari Ini?
            </h2>
            <p class="text-sm sm:text-base text-teal-100 max-w-2xl mx-auto">
                Bergabunglah dengan ratusan pemilik bisnis modern yang telah mengotomatisasi layanan pelanggan mereka bersama Wasilah AI.
            </p>
            <div class="pt-4">
                <a href="#harga" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-400 to-gold-500 hover:from-amber-500 hover:to-amber-600 px-9 py-4 text-sm font-black text-slate-900 shadow-2xl hover:scale-105 transition">
                    <span>Mulai Sekarang Tanpa Ribet</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- 8. FOOTER DENGAN LINK KEBIJAKAN PRIVASI                                   --}}
    {{-- ========================================================================= --}}
    <footer class="bg-slate-950 text-slate-400 py-12 text-xs border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">

            <div class="flex items-center space-x-2">
                <span class="text-gold-400 font-bold text-base">⚡</span>
                <span class="font-extrabold text-white text-sm">Wasilah.ai</span>
                <span class="text-slate-500">— Sarana Terbaik Layanan Pelanggan & Penjualan Otomatis.</span>
            </div>

            {{-- Navigasi Footer --}}
            <div class="flex items-center space-x-6 text-slate-400 font-medium">
                <a href="/kebijakan-privasi" class="hover:text-teal-400 transition underline underline-offset-4">Kebijakan Privasi</a>
                <a href="/admin" class="hover:text-teal-400 transition">Masuk Dashboard</a>
            </div>

            <div class="text-slate-500">
                © {{ date('Y') }} Wasilah AI SaaS. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
