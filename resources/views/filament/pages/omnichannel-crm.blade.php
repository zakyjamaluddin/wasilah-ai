<x-filament-panels::page>
    {{-- Pemuat Tailwind Engine Otomatis --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff', 100: '#e0e7ff', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 900: '#312e81'
                        }
                    }
                }
            }
        }
    </script>

    <div wire:poll.3000ms class="grid grid-cols-12 gap-0 h-[calc(100vh-13rem)] bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden text-gray-800 dark:text-gray-200">

        {{-- ========================================================================= --}}
        {{-- KOLOM 1: DAFTAR CHAT OMNICHANNEL (SIDEBAR KIRI: LEBAR 3/12)               --}}
        {{-- ========================================================================= --}}
        <div class="col-span-12 md:col-span-4 lg:col-span-3 border-r border-gray-200 dark:border-gray-800 flex flex-col h-full bg-gray-50/50 dark:bg-gray-900/50">

            {{-- Header & Search Box --}}
            <div class="p-3 border-b border-gray-200 dark:border-gray-800 space-y-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="🔍 Cari kontak atau nomor..."
                    class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                />

                {{-- Filter Platform (WA, FB, IG) --}}
                <div class="flex space-x-1 overflow-x-auto pb-1 text-xs">
                    <button wire:click="$set('channelFilter', 'all')" class="px-2.5 py-1 rounded-md font-medium transition {{ $channelFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">Semua</button>
                    <button wire:click="$set('channelFilter', 'whatsapp')" class="px-2.5 py-1 rounded-md font-medium transition {{ $channelFilter === 'whatsapp' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">WA</button>
                    <button wire:click="$set('channelFilter', 'fb_dm')" class="px-2.5 py-1 rounded-md font-medium transition {{ $channelFilter === 'fb_dm' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">FB</button>
                    <button wire:click="$set('channelFilter', 'ig_dm')" class="px-2.5 py-1 rounded-md font-medium transition {{ $channelFilter === 'ig_dm' ? 'bg-pink-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">IG</button>
                </div>
            </div>

            {{-- Daftar Room Chat --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->getConversationsQuery()->get() as $conv)
                    @php
                        $isSelected = $selectedConversationId === $conv->id;
                        $lastMsg = $conv->messages->first();
                    @endphp
                    <div
                        wire:click="selectConversation({{ $conv->id }})"
                        class="p-3 flex items-start space-x-3 cursor-pointer transition {{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-950/40 border-l-4 border-indigo-600' : 'hover:bg-gray-100 dark:hover:bg-gray-800/60' }}"
                    >
                        {{-- Avatar --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm uppercase shadow">
                                {{ substr($conv->contact->name ?? 'C', 0, 1) }}
                            </div>
                            <span class="absolute -bottom-1 -right-1 px-1 py-0.2 rounded-full text-[9px] font-bold {{ $conv->channel_type === 'whatsapp' ? 'bg-green-500 text-white' : ($conv->channel_type === 'fb_dm' ? 'bg-blue-500 text-white' : 'bg-pink-500 text-white') }}">
                                {{ $conv->channel_type === 'whatsapp' ? 'WA' : ($conv->channel_type === 'fb_dm' ? 'FB' : 'IG') }}
                            </span>
                        </div>

                        {{-- Nama & Cuplikan Chat --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline">
                                <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $conv->contact->name ?? $conv->contact->wa_jid }}
                                </h4>
                                <span class="text-[10px] text-gray-400">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true, true) : '' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                {{ $lastMsg ? ($lastMsg->sender_type === 'agent' ? 'Anda: ' : '') . ($lastMsg->message_type === 'image' ? '🖼️ [Foto]' : $lastMsg->message_body) : 'Belum ada pesan' }}
                            </p>
                        </div>

                        {{-- Unread Badge --}}
                        @if ($conv->unread_count > 0)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500 text-white">
                                {{ $conv->unread_count }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-gray-400">
                        Belum ada percakapan masuk.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- KOLOM 2: RUANG OBROLAN (TENGAH: LEBAR 6/12)                               --}}
        {{-- ========================================================================= --}}
        <div class="col-span-12 md:col-span-8 lg:col-span-6 flex flex-col h-full bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800">
            @if ($this->activeConversation)
                @php $active = $this->activeConversation; @endphp

                {{-- Header Room Chat --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/70 dark:bg-gray-900/70">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow">
                            {{ substr($active->contact->name ?? 'C', 0, 1) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                {{ $active->contact->name ?? $active->contact->wa_jid }}
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium {{ $active->channel_type === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $active->channel->name ?? 'WhatsApp' }}
                                </span>
                            </h3>
                            <p class="text-[11px] text-gray-400 font-mono">{{ $active->contact->phone_number ?: $active->contact->wa_jid }}</p>
                        </div>
                    </div>

                    {{-- Switch AI Bot Button --}}
                    <button
                        wire:click="toggleBot"
                        type="button"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm transition {{ $active->is_bot_active ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }}"
                    >
                        <span>🤖 Bot AI: <b>{{ $active->is_bot_active ? 'ON' : 'OFF' }}</b></span>
                    </button>
                </div>

                {{-- Message Bubbles Stream --}}
                <div class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50 dark:bg-gray-950/60 flex flex-col">
                    @forelse ($active->messages as $message)
                        @php
                            $isMe = $message->sender_type === 'agent' || $message->sender_type === 'bot';
                        @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[80%] rounded-2xl px-3.5 py-2 shadow-sm text-xs {{ $isMe ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-none border border-gray-200 dark:border-gray-700' }}">

                                {{-- Label Bot vs CS --}}
                                @if ($message->sender_type === 'bot')
                                    <span class="text-[10px] font-bold text-amber-200 block mb-0.5">🤖 AI Chatbot</span>
                                @elseif ($message->sender_type === 'agent')
                                    <span class="text-[10px] font-bold text-indigo-200 block mb-0.5">👤 CS Support</span>
                                @endif

                                {{-- Render Foto jika ada --}}
                                @if ($message->message_type === 'image' && $message->media_url)
                                    <img src="{{ $message->media_url }}" alt="Media" class="rounded-lg max-h-52 mb-1 border border-black/10 object-cover" />
                                @endif

                                {{-- Teks Pesan --}}
                                @if ($message->message_body)
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ $message->message_body }}</p>
                                @endif

                                <div class="text-[9px] mt-1 text-right {{ $isMe ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $message->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 text-xs">Mulai percakapan dengan customer ini...</div>
                    @endforelse
                </div>

                {{-- Input Bar Kirim Balasan Pesan --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                    <form wire:submit.prevent="sendReply" class="flex items-center space-x-2">
                        <input
                            type="text"
                            wire:model="replyText"
                            placeholder="Ketik balasan pesan CS... (Tekan Enter untuk kirim)"
                            class="flex-1 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        <button
                            type="submit"
                            class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow transition flex-shrink-0"
                        >
                            <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-3xl mb-3">💬</div>
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">Pilih Percakapan</h3>
                    <p class="text-xs">Klik salah satu chat di sebelah kiri untuk membuka ruang percakapan.</p>
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- KOLOM 3: PROFIL LEADS, PIPELINE & AI INSIGHTS (KANAN: LEBAR 3/12)          --}}
        {{-- ========================================================================= --}}
        <div class="hidden lg:flex lg:col-span-3 flex-col h-full bg-gray-50/60 dark:bg-gray-900/50 p-4 overflow-y-auto space-y-4">
            @if ($this->activeConversation)
                @php $contact = $this->activeConversation->contact; @endphp

                {{-- 1. Profil Leads Card --}}
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                    <div class="w-14 h-14 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg mx-auto shadow">
                        {{ substr($contact->name ?? 'C', 0, 1) }}
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mt-2">{{ $contact->name }}</h3>
                    <p class="text-xs text-gray-500 font-mono">{{ $contact->phone_number ?: $contact->wa_jid }}</p>
                </div>

                {{-- 2. Status Prospek Closing Pipeline --}}
                <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                        <span>📊 Status Prospek (Pipeline)</span>
                    </h4>
                    <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                        @foreach (['lead' => 'Baru', 'cold_prospect' => 'Cold', 'warm_prospect' => 'Warm', 'hot_prospect' => 'Hot 🔥', 'closing' => 'Closing', 'won' => 'Won 🎉'] as $key => $label)
                            <button
                                wire:click="updatePipeline('{{ $key }}')"
                                class="px-2 py-1.5 rounded-lg font-medium border text-center transition {{ $pipelineStage === $key ? 'bg-indigo-600 text-white border-indigo-600 shadow' : 'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- 3. AI Summary Box --}}
                <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                        <span>🧠 Ringkasan AI Kebutuhan Leads</span>
                    </h4>
                    <textarea
                        wire:model="aiSummary"
                        rows="4"
                        placeholder="AI akan otomatis menganalisis dan mencatat kebutuhan leads di sini..."
                        class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    ></textarea>
                    <button
                        wire:click="saveAiSummary"
                        class="w-full py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-indigo-700 dark:text-gray-200 text-xs font-semibold rounded-lg transition"
                    >
                        Simpan Catatan
                    </button>
                </div>

                
            @else
                <div class="text-center py-12 text-gray-400 text-xs">
                    Pilih percakapan untuk melihat profil dan ringkasan prospek leads.
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
