<x-filament-widgets::widget>
    <style>
        .luxury-banner {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 40%, #065f46 100%);
            border: 1px solid rgba(20, 184, 166, 0.3);
            box-shadow: 0 10px 25px -5px rgba(15, 118, 110, 0.25);
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            padding: 1.5rem;
            color: #ffffff;
        }
        .luxury-gold-badge {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: #fef08a;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            backdrop-filter: blur(8px);
        }
        .luxury-online-badge {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(52, 211, 153, 0.4);
            color: #6ee7b7;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
        }
        .luxury-gold-btn {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
            color: #0f172a;
            font-weight: 800;
            font-size: 0.75rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }
        .luxury-gold-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
            color: #020617;
        }
    </style>

    <div class="luxury-banner">

        {{-- Hiasan Cahaya Latar Belakang --}}
        <div style="position: absolute; right: -20px; top: -20px; width: 150px; height: 150px; background: rgba(16, 185, 129, 0.25); filter: blur(40px); border-radius: 50%;"></div>
        <div style="position: absolute; left: -20px; bottom: -20px; width: 150px; height: 150px; background: rgba(20, 184, 166, 0.2); filter: blur(40px); border-radius: 50%;"></div>

        <div style="position: relative; z-index: 10; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">

            {{-- Teks Sapaan & Nama Kantor --}}
            <div style="flex: 1; min-width: 250px;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span class="luxury-gold-badge">
                        OmniChat SaaS Enterprise
                    </span>
                    @if ($activeWaChannel)
                        <span class="luxury-online-badge">
                        WA Online: {{ $activeWaChannel->name }}
                        </span>
                    @endif
                </div>

                <h2 style="font-size: 1.4rem; font-weight: 800; line-height: 1.3; margin: 0; color: #ffffff;">
                    {{ $greeting }}, <span style="color: #fde047;">{{ $user->name }}</span>! 👋
                </h2>

                <p style="font-size: 0.8rem; color: #ccfbf1; margin-top: 0.4rem; line-height: 1.5; max-width: 600px;">
                    Selamat datang di pusat komando <b>{{ $office?->name }}</b>. Seluruh asisten AI dan mesin follow-up otomatis Anda sedang aktif bekerja secara 24/7.
                </p>
            </div>

            {{-- Tombol Emas Langsung ke Workspace Fullscreen --}}
            {{-- Tombol Emas Langsung ke Workspace dengan Heroicons --}}
            <div style="flex-shrink: 0;">
                <a href="{{ $workspaceUrl }}" target="_blank" class="luxury-gold-btn">
                    <span>Buka OmniChat Workspace</span>
                    <x-heroicon-o-arrow-top-right-on-square style="width: 1rem; height: 1rem; stroke-width: 2.5;" />
                </a>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
