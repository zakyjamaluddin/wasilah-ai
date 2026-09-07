@php
    $status = $status ?? 'loading';
    $qrImage = $qrImage ?? null;
@endphp

<div wire:poll.2000ms="checkStatus" class="flex flex-col items-center justify-center p-6 text-center">

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
                <img src="{{ $qrImage }}" alt="WhatsApp QR Code" class="w-64 h-64 rounded-lg" />
            </div>
            <p class="text-xs text-gray-400 animate-pulse">
                🔄 Menunggu scan dari HP Anda...
            </p>
        </div>

    @else
        {{-- TAMPILAN LOADING --}}
        <div class="flex flex-col items-center justify-center space-y-4 py-8">
            <div class="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm text-gray-500 font-medium">Menghubungkan ke server WhatsApp & membuat QR Code...</p>
        </div>
    @endif

</div>
