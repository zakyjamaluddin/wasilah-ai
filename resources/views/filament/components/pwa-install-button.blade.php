<div
    x-data="{
        deferredPrompt: null,
        canInstall: true,
        isInstalled: false,
        isIos: false,
        showIosModal: false,
        showDesktopModal: false,
        installedSuccess: false,

        init() {
            // Deteksi standalone mode (sudah terpasang)
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                this.isInstalled = true;
            }

            // Deteksi platform iOS
            const ua = window.navigator.userAgent.toLowerCase();
            this.isIos = /iphone|ipad|ipod/.test(ua) && !window.MSStream;

            // Tangkap event install PWA dari Chrome/Edge/Android
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.canInstall = true;
            });

            // Tangkap jika aplikasi berhasil di-install
            window.addEventListener('appinstalled', () => {
                this.isInstalled = true;
                this.canInstall = false;
                this.installedSuccess = true;
                this.deferredPrompt = null;
                setTimeout(() => {
                    this.installedSuccess = false;
                }, 5000);
            });

            // Registrasi Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then((reg) => {
                            console.log('[PWA] Service Worker terdaftar dengan scope:', reg.scope);
                        })
                        .catch((err) => {
                            console.warn('[PWA] Service Worker gagal registrasi:', err);
                        });
                });
            }
        },

        async triggerInstall() {
            if (this.isInstalled) {
                return;
            }

            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    this.canInstall = false;
                }
                this.deferredPrompt = null;
            } else if (this.isIos) {
                this.showIosModal = true;
            } else {
                this.showDesktopModal = true;
            }
        }
    }"
    class="fi-sidebar-pwa-sticky sticky bottom-0 z-20 w-full border-t border-gray-200/80 bg-white/95 px-3 py-3 backdrop-blur-md dark:border-white/10 dark:bg-gray-900/95"
>
    {{-- ========================================================================= --}}
    {{-- TAMPILAN JIKA SUDAH TERPASANG                                             --}}
    {{-- ========================================================================= --}}
    <template x-if="isInstalled">
        <div class="flex items-center gap-2 rounded-xl bg-emerald-50/80 px-3 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
            <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
            <span x-show="$store.sidebar.isOpen" class="truncate">Aplikasi Terpasang</span>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- TOMBOL INSTALL AKTIF (KETIKA BELUM TERPASANG)                             --}}
    {{-- ========================================================================= --}}
    <template x-if="!isInstalled">
        <div>
            {{-- Versi Sidebar Terbuka / Expanded --}}
            <div x-show="$store.sidebar.isOpen" class="transition-all">
                <button
                    type="button"
                    @click="triggerInstall()"
                    class="group relative flex w-full items-center justify-between gap-3 overflow-hidden rounded-xl bg-gradient-to-r from-teal-600 via-emerald-600 to-teal-700 p-2.5 text-left text-white shadow-md shadow-emerald-700/20 transition-all hover:scale-[1.02] hover:shadow-lg hover:shadow-emerald-700/30 active:scale-[0.98] cursor-pointer"
                    title="Install Wasilah AI PWA di Windows, Android, atau iOS"
                >
                    {{-- Shimmer Effect --}}
                    <div class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 group-hover:translate-x-full"></div>

                    <div class="flex items-center gap-2.5 min-w-0">
                        {{-- Icon PWA (Favicon Mini Squircle) --}}
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/20 backdrop-blur-xs ring-1 ring-white/30 shadow-xs">
                            <svg class="h-5 w-5" viewBox="0 0 100 100">
                                <rect width="100" height="100" rx="28" fill="#0f766e" />
                                <path d="M55 16L24 55h24l-7 29 35-39H50l5-29z" fill="#fcd34d" stroke="#f59e0b" stroke-width="2" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate text-xs font-black tracking-wide uppercase text-white">Install Aplikasi</span>
                                <span class="rounded bg-white/25 px-1 py-0.2 text-[9px] font-extrabold uppercase tracking-wider text-amber-200">PWA</span>
                            </div>
                            <p class="truncate text-[10px] font-medium text-emerald-100">
                                Windows &bull; Android &bull; iOS
                            </p>
                        </div>
                    </div>

                    {{-- Download Arrow Badge --}}
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-sm transition-transform group-hover:translate-y-0.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </div>
                </button>
            </div>

            {{-- Versi Sidebar Tertutup / Collapsed (Desktop Mini Mode) --}}
            <div x-show="!$store.sidebar.isOpen" class="flex justify-center transition-all">
                <button
                    type="button"
                    @click="triggerInstall()"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md shadow-emerald-600/20 hover:scale-110 active:scale-95 transition-all cursor-pointer"
                    title="Install Aplikasi Wasilah AI (PWA)"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </button>
            </div>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- MODAL PANDUAN INSTALL KHUSUS iOS (SAFARI iPHONE & iPAD)                   --}}
    {{-- ========================================================================= --}}
    <div
        x-show="showIosModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-4 backdrop-blur-xs transition-opacity"
        @click.self="showIosModal = false"
    >
        <div
            x-show="showIosModal"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
            class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 text-slate-800 dark:text-slate-100"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <img src="/icons/icon-192.png" alt="Wasilah AI" class="h-10 w-10 rounded-2xl shadow-md shadow-emerald-600/20" />
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Pasang di Layar Utama</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Khusus iPhone & iPad (Safari)</p>
                    </div>
                </div>
                <button @click="showIosModal = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="mt-4 space-y-3.5 text-xs">
                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-black text-xs dark:bg-teal-900 dark:text-teal-200">1</div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white">Ketuk Ikon Bagikan (Share)</p>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5">Ketuk tombol <strong class="inline-flex items-center text-teal-600">Share <svg class="inline h-3.5 w-3.5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg></strong> di bilah navigasi bawah Safari.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-black text-xs dark:bg-teal-900 dark:text-teal-200">2</div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white">Pilih "Tambahkan ke Layar Utama"</p>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5">Gulir ke bawah pada menu pop-up, lalu ketuk opsi <strong>"Add to Home Screen"</strong>.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-white/5">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-800 font-black text-xs dark:bg-teal-900 dark:text-teal-200">3</div>
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white">Ketuk "Tambah" (Add)</p>
                        <p class="text-slate-500 dark:text-slate-400 mt-0.5">Ketuk tombol <strong>Tambah</strong> di sudut kanan atas untuk menyelesaikan instalasi.</p>
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="showIosModal = false"
                class="mt-5 w-full rounded-xl bg-slate-900 py-3 text-center text-xs font-bold text-white transition hover:bg-slate-800 dark:bg-teal-600 dark:hover:bg-teal-500"
            >
                Saya Mengerti
            </button>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL PANDUAN INSTALL DESKTOP & ANDROID MANUAL                            --}}
    {{-- ========================================================================= --}}
    <div
        x-show="showDesktopModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs transition-opacity"
        @click.self="showDesktopModal = false"
    >
        <div
            x-show="showDesktopModal"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 text-slate-800 dark:text-slate-100"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <img src="/icons/icon-192.png" alt="Wasilah AI" class="h-10 w-10 rounded-2xl shadow-md shadow-emerald-600/20" />
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Pasang Aplikasi PWA</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Windows, Chrome & Android</p>
                    </div>
                </div>
                <button @click="showDesktopModal = false" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="mt-4 space-y-3.5 text-xs">
                <div class="rounded-2xl bg-teal-50/70 p-3.5 dark:bg-teal-950/30 text-teal-900 dark:text-teal-200">
                    <p class="font-bold">🖥️ Untuk Komputer Windows / Mac:</p>
                    <p class="text-slate-600 dark:text-slate-300 mt-1">
                        Klik ikon <strong>Install (Komputer kecil dengan panah bawah)</strong> di ujung kanan bilah alamat (URL bar) browser Chrome / Edge Anda.
                    </p>
                </div>

                <div class="rounded-2xl bg-emerald-50/70 p-3.5 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-200">
                    <p class="font-bold">📱 Untuk HP Android:</p>
                    <p class="text-slate-600 dark:text-slate-300 mt-1">
                        Ketuk menu <strong>titik tiga (⋮)</strong> di pojok kanan atas browser &rarr; pilih <strong>"Install aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="showDesktopModal = false"
                class="mt-5 w-full rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 py-3 text-center text-xs font-bold text-white transition hover:opacity-95 shadow-md shadow-emerald-600/20 cursor-pointer"
            >
                Tutup Panduan
            </button>
        </div>
    </div>
</div>
