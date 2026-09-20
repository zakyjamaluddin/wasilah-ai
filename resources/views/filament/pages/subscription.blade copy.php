<x-filament-panels::page>
    @php
        $office = $this->currentOffice;
        $status = $office?->effective_subscription_status ?? 'free';
        $daysRemaining = $office?->days_remaining ?? 0;
    @endphp

    @if ($office)
        {{-- 1. KARTU HERO STATUS KANTOR SAAT INI --}}
        <x-filament::section icon="heroicon-o-credit-card" icon-color="primary">
            <x-slot name="heading">
                Status Langganan: {{ $office->name }}
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::badge
                    :color="match ($status) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'inactive' => 'danger',
                        default => 'gray',
                    }"
                    size="lg">
                    ● {{ strtoupper($status) }}
                </x-filament::badge>
            </x-slot>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-top: 0.5rem;">
                {{-- Box 1: Informasi Paket --}}
                <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15);">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; opacity: 0.7;">Status Layanan</span>
                    <p style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem;">
                        @if ($status === 'active')
                            🔵 Aktif Penuh (Full Access)
                        @elseif ($status === 'expiring')
                            ⚠️ Hampir Habis (Expiring Soon)
                        @elseif ($status === 'inactive')
                            🔴 Kedaluwarsa (Expired)
                        @else
                            ⚪ Paket Free (Belum Berlangganan)
                        @endif
                    </p>
                    <p style="font-size: 0.8rem; opacity: 0.8; margin-top: 0.5rem;">
                        AI Chatbot, VPS Baileys WhatsApp, Meta Graph & OmniChat Workspace.
                    </p>
                </div>

                {{-- Box 2: Tanggal Berakhir --}}
                <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15);">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; opacity: 0.7;">Berakhir Pada</span>
                    <p style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem;">
                        {{ $office->expired_at ? $office->expired_at->format('d M Y, H:i') : 'Belum Diatur' }}
                    </p>
                    <p style="font-size: 0.8rem; margin-top: 0.5rem;">
                        @if ($office->expired_at && !$office->expired_at->isPast())
                            Sisa Waktu: <strong style="color: #10b981;">{{ $daysRemaining }} Hari</strong>
                        @else
                            Sisa Waktu: <strong style="color: #ef4444;">0 Hari</strong>
                        @endif
                    </p>
                </div>

                {{-- Box 3: Tombol Aksi --}}
                <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15); display: flex; flex-direction: column; justify-content: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; opacity: 0.7;">Tindakan</span>
                    <x-filament::button
                        wire:click="renewSubscription({{ $office->id }})"
                        color="primary"
                        icon="heroicon-m-sparkles"
                        size="md">
                        {{ $status === 'active' || $status === 'expiring' ? 'Perpanjang Masa Aktif' : 'Aktivasi Paket Sekarang' }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- 2. DAFTAR SEMUA KANTOR YANG DIKELOLA USER --}}
        <x-filament::section icon="heroicon-o-building-office-2" style="margin-top: 1.5rem;">
            <x-slot name="heading">
                Daftar Kantor Cabang yang Anda Kelola
            </x-slot>

            <div style="overflow-x: auto; margin-top: 0.5rem;">
                <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(128, 128, 128, 0.2); font-weight: 600; opacity: 0.7; text-transform: uppercase; font-size: 0.75rem;">
                            <th style="padding: 0.75rem 1rem;">Nama Kantor</th>
                            <th style="padding: 0.75rem 1rem;">Status</th>
                            <th style="padding: 0.75rem 1rem;">Berakhir Pada</th>
                            <th style="padding: 0.75rem 1rem;">Sisa Waktu</th>
                            <th style="padding: 0.75rem 1rem; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->userOffices as $item)
                            @php
                                $itemStatus = $item->effective_subscription_status;
                                $isCurrent = $office->id === $item->id;
                            @endphp
                            <tr style="border-bottom: 1px solid rgba(128, 128, 128, 0.1); {{ $isCurrent ? 'background: rgba(16, 185, 129, 0.06);' : '' }}">
                                <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                    {{ $item->name }}
                                    @if ($isCurrent)
                                        <x-filament::badge color="success" size="xs" style="margin-left: 0.5rem;">
                                            Sedang Dibuka
                                        </x-filament::badge>
                                    @endif
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    <x-filament::badge
                                        :color="match ($itemStatus) {
                                            'active' => 'success',
                                            'expiring' => 'warning',
                                            'inactive' => 'danger',
                                            default => 'gray',
                                        }"
                                        size="sm">
                                        ● {{ ucfirst($itemStatus) }}
                                    </x-filament::badge>
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    {{ $item->expired_at ? $item->expired_at->format('d M Y') : '-' }}
                                </td>
                                <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                    @if ($item->expired_at && !$item->expired_at->isPast())
                                        <span style="color: {{ $item->days_remaining <= 7 ? '#d97706' : '#10b981' }};">
                                            {{ $item->days_remaining }} Hari
                                        </span>
                                    @else
                                        <span style="color: #ef4444;">Kedaluwarsa</span>
                                    @endif
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: right;">
                                    <x-filament::button
                                        wire:click="renewSubscription({{ $item->id }})"
                                        color="gray"
                                        size="xs"
                                        outlined>
                                        Perpanjang ↗
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
