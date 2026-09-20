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
                <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15);">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; opacity: 0.7;">Status Layanan</span>
                    <p style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem;">
                        @if ($status === 'active')
                            🟢 Aktif Penuh (Full Access)
                        @elseif ($status === 'expiring')
                            🟡 Hampir Habis (Expiring Soon)
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

                <div style="padding: 1rem; border-radius: 0.5rem; background: rgba(128, 128, 128, 0.05); border: 1px solid rgba(128, 128, 128, 0.15); display: flex; flex-direction: column; justify-content: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; opacity: 0.7;">Tindakan</span>
                    <x-filament::button 
                        wire:click="openPaymentModal({{ $office->id }})"
                        color="primary"
                        icon="heroicon-m-sparkles"
                        size="md">
                        {{ $status === 'active' || $status === 'expiring' ? 'Perpanjang Langganan' : 'Aktivasi Paket Sekarang' }}
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
                                        wire:click="openPaymentModal({{ $item->id }})"
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

    {{-- ========================================================================= --}}
    {{-- 🎁 MODAL POPUP PILIHAN PAKET LYNK.ID                                     --}}
    {{-- ========================================================================= --}}
    @if ($showCheckoutModal)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem; backdrop-filter: blur(4px);">
            <div style="width: 100%; max-width: 480px; background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); color: #1e293b;" class="dark:bg-gray-900 dark:text-white">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(128,128,128,0.2); padding-bottom: 0.75rem;">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Pilih Paket Langganan</h3>
                        <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">Pilih durasi paket untuk kantor Anda</p>
                    </div>
                    <button wire:click="$set('showCheckoutModal', false)" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #94a3b8;">✕</button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.25rem;">
                    {{-- Opsi 1: 1 Bulan --}}
                    <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-radius: 0.75rem; border: 2px solid {{ $selectedPlan === '1_month' ? '#0d9488' : '#e2e8f0' }}; background: {{ $selectedPlan === '1_month' ? 'rgba(13, 148, 136, 0.05)' : 'transparent' }}; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="radio" wire:model.live="selectedPlan" value="1_month" name="plan">
                            <div>
                                <h4 style="font-weight: 700; font-size: 0.95rem; margin: 0;">Paket 1 Bulan</h4>
                                <span style="font-size: 0.75rem; color: #64748b;">Masa aktif 30 Hari Full AI Engine</span>
                            </div>
                        </div>
                        <span style="font-weight: 800; font-size: 1rem; color: #0d9488;">Rp 37.000</span>
                    </label>

                    {{-- Opsi 2: 1 Tahun --}}
                    <label style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-radius: 0.75rem; border: 2px solid {{ $selectedPlan === '1_year' ? '#0d9488' : '#e2e8f0' }}; background: {{ $selectedPlan === '1_year' ? 'rgba(13, 148, 136, 0.05)' : 'transparent' }}; cursor: pointer; position: relative;">
                        <span style="position: absolute; top: -0.6rem; right: 1rem; background: #f59e0b; color: white; font-size: 0.65rem; font-weight: 800; padding: 0.15rem 0.5rem; rounded-full: 9999px; border-radius: 9999px;">HEMAT 32% 🔥</span>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="radio" wire:model.live="selectedPlan" value="1_year" name="plan">
                            <div>
                                <h4 style="font-weight: 700; font-size: 0.95rem; margin: 0;">Paket 1 Tahun (12 Bulan)</h4>
                                <span style="font-size: 0.75rem; color: #64748b;">Masa aktif 365 Hari Nonstop</span>
                            </div>
                        </div>
                        <span style="font-weight: 800; font-size: 1rem; color: #0d9488;">Rp 300.000</span>
                    </label>
                </div>

                {{-- Catatan Email Penting --}}
                <div style="margin-top: 1rem; padding: 0.75rem; border-radius: 0.5rem; background: #f8fafc; border: 1px dashed #cbd5e1; font-size: 0.75rem; color: #475569;" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                    💡 <strong>Penting:</strong> Saat checkout di Lynk.id, pastikan Anda mengisi kolom email dengan: <strong style="color: #0d9488;">{{ auth()->user()->email }}</strong> agar sistem Wasilah AI langsung mengaktifkan langganan Anda secara otomatis.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem;">
                    <x-filament::button wire:click="$set('showCheckoutModal', false)" color="gray" size="sm">
                        Batal
                    </x-filament::button>
                    <x-filament::button wire:click="proceedToLynkPayment" color="primary" icon="heroicon-m-arrow-top-right-on-square" size="sm">
                        Lanjut ke Pembayaran Lynk.id ↗
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>