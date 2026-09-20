@php
    $tenant = \Filament\Facades\Filament::getTenant();
@endphp

@if ($tenant instanceof \App\Models\Office)
    @php
        $status = $tenant->effective_subscription_status;
        $daysRemaining = $tenant->days_remaining ?? 0;
        $subscriptionUrl = route('filament.admin.pages.subscription', ['tenant' => $tenant->slug]);
    @endphp

    {{-- 🟡 ALERT KUNING: EXPIRING (H-7 Menjelang Habis) --}}
    @if ($status === 'expiring')
        <div style="margin-top: 1.5rem; margin-bottom: 1.5rem; padding: 1rem; border-radius: 0.75rem; border: 1px solid #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(245, 158, 11, 0.04)); display: flex; flex-direction: column; gap: 0.75rem;" class="fi-subscription-banner">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.2); color: #d97706;">
                        <x-heroicon-s-exclamation-triangle style="width: 1.5rem; height: 1.5rem;" />
                    </div>
                    <div>
                        <h4 style="font-weight: 700; font-size: 0.95rem; margin: 0; color: #b45309;">
                            Peringatan: Masa Aktif Langganan Kantor Tersisa {{ $daysRemaining }} Hari Lagi!
                        </h4>
                        <p style="font-size: 0.8rem; margin: 0.2rem 0 0 0; opacity: 0.85;">
                            Langganan kantor <strong>{{ $tenant->name }}</strong> akan berakhir pada <strong>{{ $tenant->expired_at?->format('d M Y') }}</strong>. Segera lakukan perpanjangan agar AI & WhatsApp bot tidak terhenti.
                        </p>
                    </div>
                </div>
                <div>
                    <x-filament::button
                        tag="a"
                        href="{{ $subscriptionUrl }}"
                        color="warning"
                        size="sm"
                        icon="heroicon-m-arrow-up-right">
                        Perpanjang Sekarang
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    {{-- 🔴 ALERT MERAH: INACTIVE / FREE (Akses Terkunci) --}}
    @if ($status === 'inactive' || $status === 'free')
        <div style="margin-bottom: 1.5rem; margin-top: 1.5rem; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e11d48; background: linear-gradient(135deg, rgba(225, 29, 72, 0.12), rgba(225, 29, 72, 0.04)); display: flex; flex-direction: column; gap: 0.75rem;" class="fi-subscription-banner">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="padding: 0.5rem; border-radius: 0.5rem; background: rgba(225, 29, 72, 0.2); color: #e11d48;">
                        <x-heroicon-s-lock-closed style="width: 1.5rem; height: 1.5rem;" />
                    </div>
                    <div>
                        <h4 style="font-weight: 700; font-size: 0.95rem; margin: 0; color: #be123c;">
                            🔒 Akses Fitur Operasional Terkunci ({{ $status === 'free' ? 'Belum Berlangganan' : 'Langganan Kedaluwarsa' }})
                        </h4>
                        <p style="font-size: 0.8rem; margin: 0.2rem 0 0 0; opacity: 0.85;">
                            Masa aktif kantor <strong>{{ $tenant->name }}</strong> {{ $status === 'free' ? 'belum diaktifkan' : 'telah berakhir pada ' . $tenant->expired_at?->format('d M Y') }}. Lakukan aktivasi untuk membuka menu Channels, OmniChat CRM, dan AI Chatbot.
                        </p>
                    </div>
                </div>
                <div>
                    <x-filament::button
                        tag="a"
                        href="{{ $subscriptionUrl }}"
                        color="danger"
                        size="sm"
                        icon="heroicon-m-sparkles">
                        Aktivasi Paket Langganan
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
@endif
