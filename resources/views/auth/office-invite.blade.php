<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Undangan Pengelola | {{ $office->name }} - Wasilah AI</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-950 text-slate-100 font-sans antialiased relative overflow-hidden">

    {{-- Background Glow Effects --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-teal-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        {{-- Card Utama --}}
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl text-center space-y-6">

            {{-- Logo & Badge --}}
            <div class="flex flex-col items-center">
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-4 ring-4 ring-emerald-500/10">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Undangan Pengelola Kantor
                </span>
            </div>

            {{-- Nama Kantor & Deskripsi --}}
            <div class="space-y-2">
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    {{ $office->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-sm mx-auto">
                    Anda diundang untuk menjadi <span class="text-emerald-400 font-bold">Pengelola (Admin)</span> di kantor ini pada platform <b>Wasilah AI</b>.
                </p>
            </div>

            {{-- Feature Pills --}}
            <div class="grid grid-cols-2 gap-2 text-left bg-slate-950/60 p-3 rounded-2xl border border-slate-800/80 text-[11px] text-slate-300">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span>
                    <span>Akses OmniChat Live</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span>
                    <span>Kelola Kontak Leads</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span>
                    <span>Integrasi WhatsApp & Meta</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400">✓</span>
                    <span>Automasi AI Assistant</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-3 pt-2">
                {{-- Tombol Google Login --}}
                <a
                    href="{{ route('socialite.google.redirect', ['invite' => $office->invitation_code]) }}"
                    class="w-full flex items-center justify-center gap-3 px-5 py-3.5 rounded-2xl bg-white text-slate-800 font-extrabold text-sm shadow-xl hover:bg-slate-100 hover:shadow-emerald-500/10 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer"
                >
                    <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Masuk / Daftar dengan Google</span>
                </a>

                {{-- Link Login Email Standar --}}
                <div class="text-center pt-2">
                    <a href="/admin/login" class="text-xs text-slate-400 hover:text-slate-200 transition font-medium">
                        Sudah punya akun dengan email & password? <span class="text-emerald-400 font-bold underline">Masuk di sini</span>
                    </a>
                </div>
            </div>

            {{-- Footer Note --}}
            <p class="text-[10px] text-slate-500 leading-relaxed border-t border-slate-800/80 pt-4">
                Dengan melanjutkan, Anda akan langsung terdaftar dan mendapatkan izin resmi sebagai pengelola kantor <b>{{ $office->name }}</b> di Wasilah AI.
            </p>

        </div>
    </div>

</body>
</html>
