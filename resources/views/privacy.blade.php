<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi — Wasilah AI</title>

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
                            900: '#134e4a',
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
<body class="bg-slate-50 font-sans text-slate-800 antialiased selection:bg-teal-500 selection:text-white flex flex-col min-h-screen">

    {{-- NAVBAR SEDERHANA --}}
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-teal-700 to-emerald-600 text-gold-400 font-black text-base shadow-sm">
                    ⚡
                </span>
                <span class="text-lg font-black text-slate-900">
                    Wasilah<span class="text-teal-600">.ai</span>
                </span>
            </a>
            <a href="/" class="text-xs font-bold text-slate-600 hover:text-teal-700 transition flex items-center gap-1">
                <span>← Kembali ke Beranda</span>
            </a>
        </div>
    </header>

    {{-- KONTEN UTAMA KEBIJAKAN PRIVASI --}}
    <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 py-12">
        <div class="bg-white rounded-3xl p-6 sm:p-12 border border-slate-200/80 shadow-sm space-y-8">

            {{-- Header Dokumen --}}
            <div class="border-b border-slate-100 pb-6 space-y-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800 border border-teal-200/60">
                    🔒 Perlindungan & Keamanan Data
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    KEBIJAKAN PRIVASI WASILAH AI
                </h1>
                <p class="text-xs font-semibold text-slate-400">
                    Terakhir Diperbarui: September 2026
                </p>
            </div>

            {{-- Pembuka --}}
            <p class="text-sm leading-relaxed text-slate-600">
                Di <b>Wasilah AI</b>, kami menghargai privasi Anda dan berkomitmen untuk melindungi data pribadi serta data pelanggan bisnis Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi saat Anda menggunakan platform Omnichannel CRM dan layanan automasi AI kami.
            </p>

            {{-- Poin 1 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">1</span>
                    <span>Informasi yang Kami Kumpulkan</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Kami mengumpulkan beberapa jenis informasi untuk menyediakan dan meningkatkan layanan kami:
                </p>
                <ul class="space-y-2 text-xs sm:text-sm text-slate-600 pl-4 list-disc marker:text-teal-600">
                    <li><b>Informasi Akun & Kontak:</b> Nama, alamat email, nomor telepon/WhatsApp, nama bisnis, serta riwayat transaksi/pembayaran paket subscription.</li>
                    <li><b>Integrasi Saluran Komunikasi:</b> Token akses, kredensial API, dan ID akun dari saluran pihak ketiga yang Anda hubungkan (WhatsApp, Instagram, Facebook Messenger).</li>
                    <li><b>Data Percakapan & Pelanggan (Leads):</b> Isi pesan chat, komentar, teks, data riwayat interaksi pelanggan Anda, serta berkas media (seperti foto produk atau foto bukti transfer/struk) yang diunggah oleh pelanggan untuk diproses oleh AI.</li>
                    <li><b>Data Basis Pengetahuan (Knowledge Base):</b> URL atau dokumen yang Anda masukkan ke dalam sistem untuk ditautkan dengan fitur Dynamic HTML Website Scraper dan AI Gemini.</li>
                </ul>
            </section>

            {{-- Poin 2 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">2</span>
                    <span>Penggunaan Informasi</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Data yang dikumpulkan digunakan untuk tujuan berikut:
                </p>
                <ul class="space-y-2 text-xs sm:text-sm text-slate-600 pl-4 list-disc marker:text-teal-600">
                    <li>Menyediakan dan mengoperasikan sistem Omnichannel CRM, respon otomatis berbasis AI Gemini Vision, serta fitur Drip Follow-Up.</li>
                    <li>Menganalisis teks dan gambar (seperti foto bukti transfer) untuk otomatisasi alur kerja dan penentuan status prospek (Leads).</li>
                    <li>Memproses pengiriman pesan siaran (Broadcast) sesuai instruksi yang Anda aturkan pada platform.</li>
                    <li>Pengelolaan tagihan, aktivasi paket (Starter, Pro Enterprise, Custom White-Label), dan dukungan teknis.</li>
                </ul>
            </section>

            {{-- Poin 3 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">3</span>
                    <span>Keamanan Data & Isolasi Cabang</span>
                </h2>
                <ul class="space-y-2 text-xs sm:text-sm text-slate-600 pl-4 list-disc marker:text-teal-600">
                    <li><b>Multi-Cabang Terisolasi:</b> Setiap data kantor cabang dipisahkan secara teknis untuk memastikan batasan akses dan privasi antar-cabang atau pengguna tetap terjaga.</li>
                    <li><b>Keamanan Transmisi & Penyimpanan:</b> Kami menerapkan standar keamanan digital untuk melindungi data Anda dari akses, perubahan, atau pembocoran tanpa izin.</li>
                </ul>
            </section>

            {{-- Poin 4 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">4</span>
                    <span>Penggunaan API & Pihak Ketiga</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Wasilah AI berintegrasi dengan penyedia layanan pihak ketiga untuk menjalankan fungsinya:
                </p>
                <ul class="space-y-2 text-xs sm:text-sm text-slate-600 pl-4 list-disc marker:text-teal-600">
                    <li><b>Layanan Pemrosesan AI:</b> Kami menggunakan teknologi AI (seperti Google Gemini Vision) untuk menganalisis isi pesan teks dan gambar. Data diproses semata-mata untuk menghasilkan respons otomatis sesuai knowledge base bisnis Anda.</li>
                    <li><b>Platform Pesan:</b> Interaksi dengan WhatsApp, Instagram, dan Facebook tunduk pada kebijakan privasi dan ketentuan platform masing-masing.</li>
                </ul>
            </section>

            {{-- Poin 5 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">5</span>
                    <span>Kerahasiaan & Pembagian Data</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Kami <b>tidak menjual, menyewakan, atau memperdagangkan</b> data pribadi Anda maupun data leads Anda kepada pihak ketiga mana pun. Data hanya dapat diakses oleh pihak berwenang apabila diwajibkan oleh hukum yang berlaku.
                </p>
            </section>

            {{-- Poin 6 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">6</span>
                    <span>Hak Pengguna</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Sebagai pengguna layanan Wasilah AI, Anda berhak untuk:
                </p>
                <ul class="space-y-2 text-xs sm:text-sm text-slate-600 pl-4 list-disc marker:text-teal-600">
                    <li>Mengakses, memperbarui, atau menghapus data kontak dan leads dari workspace Anda.</li>
                    <li>Memutuskan tautan integrasi WhatsApp, Instagram, atau Facebook dari platform kami kapan saja.</li>
                    <li>Mengunduh atau menghapus data riwayat percakapan dari sistem kami.</li>
                </ul>
            </section>

            {{-- Poin 7 --}}
            <section class="space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">7</span>
                    <span>Perubahan Kebijakan Privasi</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Setiap perubahan akan diinformasikan melalui situs web resmi kami atau pemberitahuan di dalam platform Wasilah AI.
                </p>
            </section>

            {{-- Poin 8 --}}
            <section class="space-y-3 pt-4 border-t border-slate-100">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-teal-100 text-teal-800 text-xs font-bold">8</span>
                    <span>Hubungi Kami</span>
                </h2>
                <p class="text-xs sm:text-sm leading-relaxed text-slate-600">
                    Jika Anda memiliki pertanyaan terkait Kebijakan Privasi ini atau pengelolaan data Anda, silakan hubungi tim kami melalui email:
                </p>
                <div class="inline-flex items-center gap-2 rounded-xl bg-teal-50 border border-teal-200 px-4 py-2.5 text-xs sm:text-sm font-bold text-teal-900">
                    <span>📧</span>
                    <a href="mailto:info@zakyapps.my.id" class="hover:underline">info@zakyapps.my.id</a>
                </div>
            </section>

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="bg-slate-950 text-slate-400 py-8 text-xs border-t border-slate-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <span class="text-gold-400 font-bold text-sm">⚡</span>
                <span class="font-extrabold text-white">Wasilah.ai</span>
                <span>— Seluruh hak cipta dilindungi.</span>
            </div>
            <div>
                © {{ date('Y') }} Wasilah AI SaaS.
            </div>
        </div>
    </footer>

</body>
</html>
