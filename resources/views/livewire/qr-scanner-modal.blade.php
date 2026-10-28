@php
    $status = $status ?? 'loading';
    $qrImage = $qrImage ?? null;
    $errorMessage = $errorMessage ?? null;
@endphp

<div @if($status === 'loading' || ($status === 'scanning' && !$qrImage)) wire:poll.2000ms="checkStatus" @elseif($status === 'scanning') wire:poll.3000ms="checkStatus" @endif class="flex flex-col items-center justify-center p-6 text-center">

    @if ($status === 'connected')
        {{-- TAMPILAN JIKA SUDAH TERHUBUNG --}}
        <div class="flex flex-col items-center space-y-3">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-3xl">
                ✅
            </div>
            <h3 class="text-xl font-bold text-green-600">WhatsApp Berhasil Terhubung!</h3>
            <p class="text-sm text-gray-500">Sesi aktif dan siap menerima serta membalas pesan otomatis.</p>
        </div>

    @elseif ($status === 'scanning' && $qrImage)
        {{-- TAMPILAN QR CODE SIAP SCAN --}}
        <div class="flex flex-col items-center space-y-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Buka WhatsApp di HP > <b>Perangkat Tertaut</b> > <b>Tautkan Perangkat</b>
            </p>
            <div class="p-3 bg-white border-2 border-dashed border-gray-300 rounded-xl shadow-inner">
                <img src="{{ $qrImage }}" alt="WhatsApp QR Code" class="w-64 h-64 rounded-lg shadow-sm" />
            </div>
            <p class="text-xs text-gray-400 animate-pulse">
                🔄 Menunggu scan dari HP Anda... (Status akan otomatis terhubung)
            </p>
        </div>

    @elseif ($status === 'error')
        {{-- TAMPILAN ERROR / GAGAL KONEKSI --}}
        <div class="flex flex-col items-center justify-center space-y-3 py-6 max-w-sm">
            <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-2xl font-bold">
                ⚠️
            </div>
            <h4 class="text-base font-semibold text-red-600">Gagal Memuat QR Code</h4>
            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed break-words">
                {{ $errorMessage ?: 'Terjadi kendala saat menghubungi server Baileys di VPS.' }}
            </p>
            <button
                type="button"
                wire:click="retry"
                class="mt-2 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5 animate-spin" wire:loading wire:target="retry" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Coba Hubungkan Ulang</span>
            </button>
        </div>

    @else
        {{-- TAMPILAN LOADING --}}
        <div class="flex flex-col items-center justify-center space-y-4 py-8">
            <div class="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm text-gray-500 font-medium">Menghubungkan ke server WhatsApp & membuat QR Code...</p>
            <p class="text-xs text-gray-400">Sedang menginisialisasi sesi di VPS (port 3100)...</p>
        </div>
    @endif

</div>
