{{-- ========================================================================= --}}
    {{-- 🎁 MODAL POPUP PILIHAN PAKET LYNK.ID (DENGAN TAB BARU & AUTO-POLL)        --}}
    {{-- ========================================================================= --}}
    @if ($showCheckoutModal)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.6); padding: 1rem; backdrop-filter: blur(4px);">
            
            {{-- AUTO-POLL: Cek status bayar tiap 3 detik saat menunggu pembayaran --}}
            <div @if($waitingForPayment) wire:poll.3000ms="checkPaymentStatus" @endif
                 style="width: 100%; max-width: 480px; background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); color: #1e293b;" class="dark:bg-gray-900 dark:text-white">
                
                @if (!$waitingForPayment)
                    {{-- 🟢 STEP 1: PILIH PAKET --}}
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
                            <span style="position: absolute; top: -0.6rem; right: 1rem; background: #f59e0b; color: white; font-size: 0.65rem; font-weight: 800; padding: 0.15rem 0.5rem; border-radius: 9999px;">HEMAT 32% 🔥</span>
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

                    {{-- Catatan Email --}}
                    <div style="margin-top: 1rem; padding: 0.75rem; border-radius: 0.5rem; background: #f8fafc; border: 1px dashed #cbd5e1; font-size: 0.75rem; color: #475569;" class="dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                        💡 <strong>Penting:</strong> Saat checkout di Lynk.id, pastikan Anda mengisi email dengan: <strong style="color: #0d9488;">{{ auth()->user()->email }}</strong>. Link pembayaran akan dibuka di <b>tab baru</b>.
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem;">
                        <x-filament::button wire:click="$set('showCheckoutModal', false)" color="gray" size="sm">
                            Batal
                        </x-filament::button>
                        <x-filament::button wire:click="proceedToLynkPayment" color="primary" icon="heroicon-m-arrow-top-right-on-square" size="sm">
                            Buka Pembayaran (Tab Baru) ↗
                        </x-filament::button>
                    </div>

                @else
                    {{-- 🟡 STEP 2: MENUNGGU PEMBAYARAN DI TAB BARU (ANIMASI POLLING) --}}
                    <div style="text-align: center; padding: 1rem 0;">
                        <div style="width: 4rem; height: 4rem; border-radius: 9999px; background: rgba(13, 148, 136, 0.1); color: #0d9488; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                            <x-heroicon-o-arrow-path style="width: 2rem; height: 2rem; animation: spin 2s linear infinite;" />
                        </div>

                        <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0; color: #0f766e;">Menunggu Pembayaran Anda...</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; line-height: 1.5;">
                            Halaman pembayaran Lynk.id telah dibuka di <b>tab baru browser Anda</b>.<br>
                            Silakan selesaikan pembayaran QRIS / Bank Transfer Anda di tab tersebut.
                        </p>

                        <div style="margin-top: 1.25rem; padding: 0.75rem; border-radius: 0.5rem; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); font-size: 0.75rem; color: #b45309;">
                            ⏳ Halaman ini otomatis mengecek status pembayaran Anda setiap beberapa detik. Jangan tutup halaman ini sampai pembayaran selesai.
                        </div>

                        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;">
                            <x-filament::button wire:click="checkPaymentStatus" color="primary" icon="heroicon-m-arrow-path" size="sm">
                                Cek Status Sekarang
                            </x-filament::button>
                            <x-filament::button wire:click="$set('showCheckoutModal', false)" color="gray" size="sm">
                                Tutup Jendela
                            </x-filament::button>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif