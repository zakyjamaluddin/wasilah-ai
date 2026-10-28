<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <!-- 🔥 VIEWPORT FIT COVER UNTUK ANDROID & IPHONE NOTCH -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Wasilah AI | OmniChat Live Workspace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <!-- PWA Primary Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0f766e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Wasilah AI">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <meta name="application-name" content="Wasilah AI">
    <meta name="msapplication-TileColor" content="#0f766e">
    <meta name="msapplication-TileImage" content="/icons/icon-512.png">
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
                        tosca: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        },
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --sar: env(safe-area-inset-right, 0px);
        }
        /* 🔥 KHUSUS LAYAR PONSEL (MOBILE): PADDING AMAN DARI TOPBAR PONSEL (BATERAI, JAM, SINYAL, NOTCH) */
        @media (max-width: 767px) {
            .safe-pt-header {
                padding-top: max(3.5rem, calc(env(safe-area-inset-top, 0px) + 0.75rem)) !important;
            }
            .safe-top {
                padding-top: max(3.5rem, calc(env(safe-area-inset-top, 0px) + 0.75rem)) !important;
            }
            .safe-pb-input {
                padding-bottom: max(1.25rem, calc(env(safe-area-inset-bottom, 0px) + 0.5rem)) !important;
            }
            .safe-bottom {
                padding-bottom: max(1.25rem, calc(env(safe-area-inset-bottom, 0px) + 0.5rem)) !important;
            }
        }

        /* 💻 LAYAR TABLET & DESKTOP: PADDING HEADER NORMAL & KOMPAK */
        @media (min-width: 768px) {
            .safe-pt-header {
                padding-top: 0.75rem !important;
            }
            .safe-top {
                padding-top: 0.75rem !important;
            }
            .safe-pb-input {
                padding-bottom: 0.875rem !important;
            }
            .safe-bottom {
                padding-bottom: 0.875rem !important;
            }
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .touch-scroll {
            -webkit-overflow-scrolling: touch;
        }
    </style>
    @livewireStyles
</head>
<body class="h-full w-full overflow-hidden bg-slate-100 font-sans text-slate-800 antialiased">
    {{ $slot }}
    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => console.warn(err));
            });
        }
    </script>
</body>
</html>