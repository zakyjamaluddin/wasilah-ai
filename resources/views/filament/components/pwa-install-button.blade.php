<style>
    .fi-sidebar-pwa-container {
        margin-top: auto !important;
        position: sticky !important;
        bottom: 0 !important;
        z-index: 25 !important;
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 10px 12px !important;
        background: #ffffff !important;
        border-top: 1px solid #e2e8f0 !important;
    }
    .dark .fi-sidebar-pwa-container {
        background: #111827 !important;
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }
    .fi-pwa-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100% !important;
        padding: 8px 10px !important;
        border-radius: 12px !important;
        background: linear-gradient(135deg, #0f766e 0%, #059669 100%) !important;
        color: #ffffff !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.28) !important;
        text-align: left !important;
        text-decoration: none !important;
        box-sizing: border-box !important;
        gap: 8px !important;
        transition: transform 0.15s ease, filter 0.15s ease !important;
    }
    .fi-pwa-btn:hover {
        filter: brightness(1.05) !important;
        transform: translateY(-1px) !important;
    }
    .fi-pwa-btn:active {
        transform: translateY(1px) !important;
    }
    .fi-pwa-compact-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        min-height: 36px !important;
        max-width: 36px !important;
        max-height: 36px !important;
        margin: 0 auto !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #0f766e 0%, #059669 100%) !important;
        color: #ffffff !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.28) !important;
        transition: transform 0.15s ease, filter 0.15s ease !important;
    }
    .fi-pwa-compact-btn:hover {
        filter: brightness(1.08) !important;
        transform: scale(1.06) !important;
    }
    .dark .fi-pwa-modal-card {
        background: #1f2937 !important;
        color: #f3f4f6 !important;
    }
    .dark .fi-pwa-modal-title {
        color: #ffffff !important;
    }
    .dark .fi-pwa-modal-desc {
        color: #9ca3af !important;
    }
    .dark .fi-pwa-step-card {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #f3f4f6 !important;
    }
    .dark .fi-pwa-step-card p {
        color: #d1d5db !important;
    }
</style>

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

            // Deteksi platform iOS (Safari)
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
    class="fi-sidebar-pwa-container"
>
    {{-- ========================================================================= --}}
    {{-- TAMPILAN JIKA SUDAH TERPASANG                                             --}}
    {{-- ========================================================================= --}}
    <template x-if="isInstalled">
        <div style="display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); color: #047857; font-size: 11px; font-weight: 700; width: 100%; box-sizing: border-box;">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 15px; height: 15px; flex-shrink: 0; display: block;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
            <span x-show="$store.sidebar.isOpen" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Aplikasi Terpasang</span>
        </div>
    </template>

    {{-- ========================================================================= --}}
    {{-- TOMBOL INSTALL AKTIF (KETIKA BELUM TERPASANG)                             --}}
    {{-- ========================================================================= --}}
    <template x-if="!isInstalled">
        <div style="width: 100%;">
            {{-- Versi Sidebar Terbuka / Expanded --}}
            <div x-show="$store.sidebar.isOpen" style="width: 100%;">
                <button
                    type="button"
                    @click="triggerInstall()"
                    class="fi-pwa-btn"
                    title="Install Wasilah AI PWA di Windows, Android, atau iOS"
                >
                    <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1 1 0%;">
                        {{-- Icon PWA (Favicon Mini Squircle) --}}
                        <div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; border-radius: 8px; background: rgba(255, 255, 255, 0.22); overflow: hidden; flex-shrink: 0;">
                            <svg width="20" height="20" viewBox="0 0 100 100" style="width: 20px; height: 20px; min-width: 20px; min-height: 20px; max-width: 20px; max-height: 20px; display: block;">
                                <rect width="100" height="100" rx="28" fill="#0f766e" />
                                <path d="M55 16L24 55h24l-7 29 35-39H50l5-29z" fill="#fcd34d" stroke="#f59e0b" stroke-width="2" />
                            </svg>
                        </div>

                        <div style="min-width: 0; flex: 1 1 0%; display: flex; flex-direction: column;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="font-size: 11px; font-weight: 800; letter-spacing: 0.03em; text-transform: uppercase; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">Install Aplikasi</span>
                                <span style="background: rgba(255, 255, 255, 0.25); color: #fef08a; font-size: 8px; font-weight: 900; padding: 1px 4px; border-radius: 4px; text-transform: uppercase; line-height: 1; flex-shrink: 0;">PWA</span>
                            </div>
                            <span style="font-size: 9px; color: rgba(204, 251, 241, 0.9); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">
                                Windows &bull; Android &bull; iOS
                            </span>
                        </div>
                    </div>

                    {{-- Download Arrow Badge --}}
                    <div style="display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; min-width: 24px; min-height: 24px; max-width: 24px; max-height: 24px; border-radius: 6px; background: #ffffff; color: #0f766e; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 13px; height: 13px; min-width: 13px; min-height: 13px; max-width: 13px; max-height: 13px; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </div>
                </button>
            </div>

            {{-- Versi Sidebar Tertutup / Collapsed (Desktop Mini Mode) --}}
            <div x-show="!$store.sidebar.isOpen" style="display: flex; justify-content: center; width: 100%;">
                <button
                    type="button"
                    @click="triggerInstall()"
                    class="fi-pwa-compact-btn"
                    title="Install Aplikasi Wasilah AI (PWA)"
                >
                    <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 17px; height: 17px; min-width: 17px; min-height: 17px; max-width: 17px; max-height: 17px; display: block;">
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
        style="position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); padding: 16px; box-sizing: border-box;"
        @click.self="showIosModal = false"
    >
        <div
            class="fi-pwa-modal-card"
            style="background: #ffffff; color: #1e293b; border-radius: 20px; padding: 22px; max-width: 380px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.3); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-sizing: border-box;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="/icons/icon-192.png" alt="Wasilah AI" style="width: 36px; height: 36px; border-radius: 10px; display: block;" />
                    <div>
                        <h3 class="fi-pwa-modal-title" style="font-size: 14px; font-weight: 800; margin: 0; color: #0f172a;">Pasang di Layar Utama</h3>
                        <p class="fi-pwa-modal-desc" style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Khusus iPhone & iPad (Safari)</p>
                    </div>
                </div>
                <button @click="showIosModal = false" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: flex; align-items: center;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 10px; font-size: 12px;">
                <div class="fi-pwa-step-card" style="display: flex; align-items: flex-start; gap: 10px; border-radius: 12px; background: #f8fafc; padding: 10px 12px;">
                    <div style="width: 22px; height: 22px; min-width: 22px; border-radius: 6px; background: #ccfbf1; color: #0f766e; font-weight: 900; font-size: 11px; display: flex; align-items: center; justify-content: center;">1</div>
                    <div>
                        <strong style="color: #0f172a; display: block;">Ketuk Ikon Bagikan (Share)</strong>
                        <p style="color: #64748b; margin: 2px 0 0 0; font-size: 11px;">Ketuk tombol <strong>Share</strong> (ikon kotak panah ke atas) di bilah bawah Safari.</p>
                    </div>
                </div>

                <div class="fi-pwa-step-card" style="display: flex; align-items: flex-start; gap: 10px; border-radius: 12px; background: #f8fafc; padding: 10px 12px;">
                    <div style="width: 22px; height: 22px; min-width: 22px; border-radius: 6px; background: #ccfbf1; color: #0f766e; font-weight: 900; font-size: 11px; display: flex; align-items: center; justify-content: center;">2</div>
                    <div>
                        <strong style="color: #0f172a; display: block;">Pilih "Tambahkan ke Layar Utama"</strong>
                        <p style="color: #64748b; margin: 2px 0 0 0; font-size: 11px;">Gulir ke bawah pada menu pop-up, lalu ketuk opsi <strong>"Add to Home Screen"</strong>.</p>
                    </div>
                </div>

                <div class="fi-pwa-step-card" style="display: flex; align-items: flex-start; gap: 10px; border-radius: 12px; background: #f8fafc; padding: 10px 12px;">
                    <div style="width: 22px; height: 22px; min-width: 22px; border-radius: 6px; background: #ccfbf1; color: #0f766e; font-weight: 900; font-size: 11px; display: flex; align-items: center; justify-content: center;">3</div>
                    <div>
                        <strong style="color: #0f172a; display: block;">Ketuk "Tambah" (Add)</strong>
                        <p style="color: #64748b; margin: 2px 0 0 0; font-size: 11px;">Ketuk tombol <strong>Tambah</strong> di sudut kanan atas untuk menyelesaikan instalasi.</p>
                    </div>
                </div>
            </div>

            <button
                type="button"
                @click="showIosModal = false"
                style="margin-top: 18px; width: 100%; border-radius: 10px; background: #0f766e; color: #ffffff; padding: 10px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);"
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
        style="position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); padding: 16px; box-sizing: border-box;"
        @click.self="showDesktopModal = false"
    >
        <div
            class="fi-pwa-modal-card"
            style="background: #ffffff; color: #1e293b; border-radius: 20px; padding: 22px; max-width: 380px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.3); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-sizing: border-box;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="/icons/icon-192.png" alt="Wasilah AI" style="width: 36px; height: 36px; border-radius: 10px; display: block;" />
                    <div>
                        <h3 class="fi-pwa-modal-title" style="font-size: 14px; font-weight: 800; margin: 0; color: #0f172a;">Pasang Aplikasi PWA</h3>
                        <p class="fi-pwa-modal-desc" style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">Windows, Chrome & Android</p>
                    </div>
                </div>
                <button @click="showDesktopModal = false" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: flex; align-items: center;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 10px; font-size: 12px;">
                <div class="fi-pwa-step-card" style="border-radius: 12px; background: #f0fdfa; padding: 12px; border-left: 3px solid #0f766e;">
                    <strong style="color: #0f766e; display: block; font-size: 12px;">🖥️ Untuk Komputer Windows / Mac:</strong>
                    <p style="color: #475569; margin: 4px 0 0 0; font-size: 11px; line-height: 1.5;">
                        Klik ikon <strong>Install (Komputer kecil dengan panah bawah)</strong> di ujung kanan bilah alamat (URL bar) browser Chrome / Edge Anda.
                    </p>
                </div>

                <div class="fi-pwa-step-card" style="border-radius: 12px; background: #ecfdf5; padding: 12px; border-left: 3px solid #059669;">
                    <strong style="color: #059669; display: block; font-size: 12px;">📱 Untuk HP Android:</strong>
                    <p style="color: #475569; margin: 4px 0 0 0; font-size: 11px; line-height: 1.5;">
                        Ketuk menu <strong>titik tiga (⋮)</strong> di pojok kanan atas browser &rarr; pilih <strong>"Install aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="showDesktopModal = false"
                style="margin-top: 18px; width: 100%; border-radius: 10px; background: linear-gradient(135deg, #0f766e 0%, #059669 100%); color: #ffffff; padding: 10px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);"
            >
                Tutup Panduan
            </button>
        </div>
    </div>
</div>
