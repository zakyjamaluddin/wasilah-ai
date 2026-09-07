<div wire:poll.2500ms class="flex h-screen w-screen flex-col overflow-hidden bg-white text-slate-800">

    {{-- ========================================================================= --}}
    {{-- 1. TOP NAVBAR (GRADASI TOSCA-EMERALD DENGAN AKSEN GOLD)                   --}}
    {{-- ========================================================================= --}}

    {{-- ========================================================================= --}}
    {{-- 2. 3-COLUMN MAIN WORKSPACE                                                --}}
    {{-- ========================================================================= --}}
    <div class="relative flex flex-1 min-h-0 w-full overflow-hidden bg-slate-50/50">

        {{-- ========================================================================= --}}
        {{-- KOLOM 1: DAFTAR CHAT & 5 TAB FILTER IKON (LEBAR 3/12)                     --}}
        {{-- ========================================================================= --}}
        <div class="w-full md:w-4/12 lg:w-3/12 flex-shrink-0 flex-col border-r border-slate-200/80 bg-white {{ $mobileView === 'list' ? 'flex' : 'hidden md:flex' }} h-full min-h-0 shadow-[2px_0_10px_-3px_rgba(0,0,0,0.03)] z-10">

            {{-- Search Bar & Tombol Chat Baru (+) --}}
            <div class="space-y-2.5 border-b border-slate-100 p-3 bg-slate-50/60">
                <div class="flex items-center space-x-2">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="Cari kontak, nomor, @ig..."
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 transition shadow-sm"
                        />
                        <svg class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    {{-- Tombol Mulai Chat Baru (+) --}}
                    <button
                        wire:click="openNewChatModal"
                        title="Mulai Chat Baru"
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md hover:shadow-teal-500/20 hover:scale-105 transition flex-shrink-0"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                </div>

                {{-- 🔥 5 TAB FILTER OMNICHANNEL DENGAN IKON & TOOLTIP --}}
                <div class="grid grid-cols-5 gap-1 rounded-xl bg-slate-200/60 p-1 text-xs">

                    {{-- 1. Semua --}}
                    <button
                        wire:click="$set('tabFilter', 'all')"
                        title="Semua Saluran"
                        class="flex items-center justify-center py-1.5 rounded-lg transition {{ $tabFilter === 'all' ? 'bg-white text-teal-700 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        <span class="text-xs">🌟 Semua</span>
                    </button>

                    {{-- 2. WhatsApp --}}
                    <button
                        wire:click="$set('tabFilter', 'whatsapp')"
                        title="Khusus WhatsApp Personal"
                        class="flex items-center justify-center py-1.5 rounded-lg transition {{ $tabFilter === 'whatsapp' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-500 hover:text-emerald-600' }}"
                    >
                        <span class="text-xs font-bold">💬 WA</span>
                    </button>

                    {{-- 3. Facebook --}}
                    <button
                        wire:click="$set('tabFilter', 'facebook')"
                        title="Khusus Facebook (DM & Komentar)"
                        class="flex items-center justify-center py-1.5 rounded-lg transition {{ $tabFilter === 'facebook' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'text-slate-500 hover:text-blue-600' }}"
                    >
                        <span class="text-xs font-bold">📘 FB</span>
                    </button>

                    {{-- 4. Instagram --}}
                    <button
                        wire:click="$set('tabFilter', 'instagram')"
                        title="Khusus Instagram (DM & Komentar)"
                        class="flex items-center justify-center py-1.5 rounded-lg transition {{ $tabFilter === 'instagram' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold shadow-sm' : 'text-slate-500 hover:text-pink-600' }}"
                    >
                        <span class="text-xs font-bold">📷 IG</span>
                    </button>

                    {{-- 5. Grup WA --}}
                    <button
                        wire:click="$set('tabFilter', 'group')"
                        title="Khusus Grup WhatsApp"
                        class="flex items-center justify-center py-1.5 rounded-lg transition {{ $tabFilter === 'group' ? 'bg-teal-700 text-white font-bold shadow-sm' : 'text-slate-500 hover:text-teal-700' }}"
                    >
                        <span class="text-xs font-bold">👥 Grup</span>
                    </button>
                </div>
            </div>

            {{-- Daftar List Chat --}}
            <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-slate-100/80">
                @forelse ($conversations as $conv)
                    @php
                        $isSelected = $selectedConversationId === $conv->id;
                        $lastMsg = $conv->latestMessage;
                        $isGroupChat = str_contains($conv->contact->wa_jid ?? '', '@g.us');
                        $avatarBg = $this->getAvatarColor($conv->contact->name ?? 'User');
                    @endphp
                    <div
                        wire:click="selectConversation({{ $conv->id }})"
                        class="flex cursor-pointer items-start space-x-3 p-3.5 transition {{ $isSelected ? 'bg-teal-50/70 shadow-[inset_4px_0_0_0_#0d9488]' : 'hover:bg-slate-50' }}"
                    >
                        {{-- Avatar --}}
                        <div class="relative flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $avatarBg }} text-xs font-extrabold text-white shadow-sm ring-2 ring-white">
                                {{ $isGroupChat ? '👥' : substr($conv->contact->name ?? 'C', 0, 1) }}
                            </div>

                            {{-- Badge Platform Kecil --}}
                            <span class="absolute -bottom-1 -right-1 px-1 py-0.2 rounded-full text-[8px] font-black text-white shadow {{ $conv->channel_type === 'whatsapp' ? ($isGroupChat ? 'bg-teal-700' : 'bg-emerald-500') : ($conv->channel_type === 'fb_dm' || $conv->channel_type === 'fb_comment' ? 'bg-blue-600' : 'bg-pink-600') }}">
                                {{ $conv->channel_type === 'whatsapp' ? ($isGroupChat ? 'GRUP' : 'WA') : ($conv->channel_type === 'fb_dm' || $conv->channel_type === 'fb_comment' ? 'FB' : 'IG') }}
                            </span>
                        </div>

                        {{-- Nama & Cuplikan Chat --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between">
                                <h4 class="truncate text-xs font-bold {{ $isSelected ? 'text-teal-900' : 'text-slate-900' }}">
                                    {{ $conv->contact->name }}
                                </h4>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true, true) : '' }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                {{ $lastMsg ? ($lastMsg->sender_type === 'agent' ? 'Anda: ' : '') . ($lastMsg->message_type === 'image' ? '🖼️ [Foto]' : ($lastMsg->message_type === 'video' ? '🎥 [Video]' : ($lastMsg->message_type === 'document' ? '📄 [Dokumen]' : $lastMsg->message_body))) : 'Belum ada pesan' }}
                            </p>
                        </div>

                        {{-- Unread Badge --}}
                        @if ($conv->unread_count > 0)
                            <span class="flex h-4 min-w-[16px] items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 px-1 text-[9px] font-black text-white shadow-sm">
                                {{ $conv->unread_count }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">Belum ada obrolan.</div>
                @endforelse
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- KOLOM 2: CHAT ROOM WINDOW (TENGAH)                                        --}}
        {{-- ========================================================================= --}}
        <div class="w-full md:w-8/12 lg:w-6/12 flex-1 min-h-0 flex-col bg-white border-r border-slate-200/80 {{ $mobileView === 'chat' ? 'flex' : 'hidden md:flex' }}">
            @if ($active)
                @php
                    $isGroupActive = str_contains($active->contact->wa_jid ?? '', '@g.us');
                    $headerAvatarBg = $this->getAvatarColor($active->contact->name ?? 'User');
                @endphp

                {{-- Room Header --}}
                <div class="flex h-14 flex-shrink-0 items-center justify-between border-b border-slate-100 bg-white/80 backdrop-blur-md px-3 md:px-5 shadow-sm">
                    <div class="flex items-center space-x-2 md:space-x-3">
                        <button wire:click="backToMobileList" class="md:hidden p-1.5 -ml-1 text-slate-600 hover:bg-slate-100 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>

                        <div class="flex h-9 w-9 items-center justify-center rounded-2xl {{ $headerAvatarBg }} text-xs font-extrabold text-white shadow-sm ring-2 ring-teal-100">
                            {{ $isGroupActive ? '👥' : substr($active->contact->name ?? 'C', 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-xs font-extrabold text-slate-900 flex items-center gap-1.5">
                                <span class="truncate">{{ $active->contact->name }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $active->channel_type === 'whatsapp' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($active->channel_type === 'fb_dm' || $active->channel_type === 'fb_comment' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-pink-50 text-pink-700 border border-pink-200') }}">
                                    {{ strtoupper(str_replace('_', ' ', $active->channel_type)) }}
                                </span>
                            </h3>
                            <p class="truncate font-mono text-[10px] text-slate-400">{{ $active->contact->phone_number ?: $active->contact->wa_jid }}</p>
                        </div>
                    </div>

                    {{-- Switch Bot Button --}}
                    @if (!$isGroupActive)
                        <button
                            wire:click="toggleBot"
                            type="button"
                            class="flex items-center space-x-1.5 rounded-xl border px-3 py-1.5 text-[11px] font-bold shadow-sm transition {{ $active->is_bot_active ? 'border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100' : 'border-slate-200 bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        >
                            <span class="h-2 w-2 rounded-full {{ $active->is_bot_active ? 'bg-teal-500 animate-pulse' : 'bg-slate-400' }}"></span>
                            <span>Bot AI: {{ $active->is_bot_active ? 'ON' : 'OFF' }}</span>
                        </button>
                    @endif
                </div>

                {{-- MESSAGE STREAM CONTAINER --}}
                <div
                    id="chatStream"
                    x-data="{ scrollToBottom() { $el.scrollTop = $el.scrollHeight } }"
                    x-init="scrollToBottom()"
                    x-on:DOMNodeInserted.window="scrollToBottom()"
                    class="flex-1 min-h-0 overflow-y-auto p-3 md:p-4 space-y-3 bg-[#f8fafc]"
                >
                    @foreach ($active->messages as $msg)
                        @php $isMe = $msg->sender_type === 'agent' || $msg->sender_type === 'bot'; @endphp
                        <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[85%] md:max-w-[72%] rounded-2xl px-4 py-2.5 text-xs shadow-sm {{ $msg->sender_type === 'bot' ? 'bg-[#0f172a] text-slate-100 rounded-br-sm border border-slate-700/60' : ($msg->sender_type === 'agent' ? 'bg-gradient-to-r from-teal-700 to-emerald-600 text-white rounded-br-sm shadow-teal-700/10' : 'bg-white text-slate-800 rounded-bl-sm border border-slate-200/80 shadow-sm') }}">

                                {{-- Label Header Bubble --}}
                                @if ($msg->sender_type === 'bot')
                                    <span class="mb-1 flex items-center gap-1 text-[10px] font-extrabold text-gold-400">
                                        <span>✨ AI Assistant</span>
                                    </span>
                                @elseif ($msg->sender_type === 'agent')
                                    <span class="mb-1 block text-[10px] font-bold text-teal-100">
                                        👤 CS Support
                                    </span>
                                @endif

                                {{-- Render Foto --}}
                                @if ($msg->message_type === 'image' && $msg->media_url)
                                    <img src="{{ $msg->media_url }}" class="mb-2 max-h-60 rounded-xl object-cover border border-black/10 shadow-sm" />
                                @endif

                                {{-- Render Video MP4 --}}
                                @if ($msg->message_type === 'video' && $msg->media_url)
                                    <video controls class="mb-2 max-h-64 rounded-xl bg-black w-full shadow-sm" preload="metadata">
                                        <source src="{{ $msg->media_url }}" type="video/mp4">
                                    </video>
                                @endif

                                {{-- Render Dokumen PDF / Excel --}}
                                @if ($msg->message_type === 'document' && $msg->media_url)
                                    <a href="{{ $msg->media_url }}" target="_blank" class="mb-2 flex items-center space-x-2 rounded-xl bg-black/10 p-2.5 hover:bg-black/20 transition">
                                        <span class="text-xl">📄</span>
                                        <span class="truncate font-bold underline text-[11px]">Unduh Dokumen Lampiran</span>
                                    </a>
                                @endif

                                {{-- Isi Teks Pesan --}}
                                @if ($msg->message_body)
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->message_body }}</p>
                                @endif

                                <div class="mt-1 text-right text-[9px] {{ $msg->sender_type === 'agent' ? 'text-teal-100' : 'text-slate-400' }}">
                                    {{ $msg->created_at->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- 🔥 INDIKATOR SAAT FILE SEDANG DIUNGGAH (UPLOADING SPINNER) --}}
                <div wire:loading wire:target="attachment" class="flex items-center space-x-2 border-t border-teal-200 bg-teal-50/80 px-4 py-2 text-xs text-teal-800">
                    <div class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-teal-600 border-t-transparent"></div>
                    <span class="font-semibold">Mengunggah file lampiran, mohon tunggu...</span>
                </div>

                {{-- 🔥 PREVIEW FILE SIAP KIRIM DENGAN TOMBOL BATAL --}}
                @if ($attachment)
                    <div class="flex items-center justify-between border-t border-teal-200 bg-teal-50 px-4 py-2 text-xs">
                        <div class="flex items-center space-x-2 truncate">
                            <span class="text-base">📎</span>
                            <span class="truncate font-bold text-teal-900">{{ $attachment->getClientOriginalName() }}</span>
                            <span class="text-[10px] text-teal-600">({{ round($attachment->getSize() / 1024, 1) }} KB)</span>
                        </div>
                        <button type="button" wire:click="$set('attachment', null)" class="ml-2 font-extrabold text-rose-600 hover:text-rose-800 transition">
                            Batal ✕
                        </button>
                    </div>
                @endif

                {{-- INPUT BAR BOTTOM DOCK --}}
                <div class="flex-shrink-0 border-t border-slate-100 bg-white p-3">
                    <form wire:submit.prevent="sendReply" class="flex items-center space-x-2">

                        {{-- Tombol Klip Lampiran (Mendukung Gambar, PDF, Word, Excel, Video MP4) --}}
                        <label class="cursor-pointer rounded-xl p-2.5 text-slate-400 transition hover:bg-teal-50 hover:text-teal-600 flex-shrink-0">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <input
                                type="file"
                                wire:model="attachment"
                                class="hidden"
                                accept="image/*,video/mp4,.pdf,.doc,.docx,.xlsx,.xls"
                            />
                        </label>

                        <input
                            type="text"
                            wire:model="replyText"
                            placeholder="Ketik balasan pesan... (Bisa sertakan lampiran)"
                            class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/20 transition"
                        />

                        {{-- Tombol Kirim Tosca-Emerald (Bebas Kedip) --}}
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="sendReply"
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md hover:shadow-teal-500/20 hover:scale-105 transition flex-shrink-0 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex flex-1 items-center justify-center text-xs text-slate-400">
                    Pilih percakapan untuk mulai berdiskusi.
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- KOLOM 3: PROFIL LEADS, PIPELINE & AI INSIGHTS (KANAN)                     --}}
        {{-- ========================================================================= --}}
        <div class="hidden lg:flex lg:w-3/12 flex-col h-full overflow-y-auto border-l border-slate-200/80 bg-slate-50/60 p-4 space-y-4 flex-shrink-0">
            @if ($active)
                @php
                    $contact = $active->contact;
                    $isGroupActive = str_contains($contact->wa_jid ?? '', '@g.us');
                    $leadAvatarBg = $this->getAvatarColor($contact->name ?? 'User');
                @endphp

                {{-- Profil Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $leadAvatarBg }} text-base font-extrabold text-white shadow-md ring-4 ring-slate-50">
                        {{ $isGroupActive ? '👥' : substr($contact->name ?? 'C', 0, 1) }}
                    </div>
                    <h3 class="mt-2.5 text-xs font-extrabold text-slate-900">{{ $contact->name }}</h3>
                    <p class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $contact->phone_number ?: $contact->wa_jid }}</p>
                </div>

                @if (!$isGroupActive)
                    {{-- Status Prospek Closing --}}
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 space-y-2.5 shadow-sm">
                        <h4 class="text-[11px] font-extrabold text-slate-700">Status Prospek (Pipeline)</h4>
                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                            @foreach (['lead' => 'Baru', 'cold_prospect' => 'Cold', 'warm_prospect' => 'Warm', 'hot_prospect' => 'Hot 🔥', 'closing' => 'Closing', 'won' => 'Won 🎉'] as $key => $label)
                                <button
                                    wire:click="updatePipeline('{{ $key }}')"
                                    class="rounded-xl border py-2 text-center font-bold transition {{ $pipelineStage === $key ? ($key === 'won' ? 'bg-emerald-600 text-white border-emerald-600 shadow' : ($key === 'hot_prospect' ? 'bg-amber-500 text-white border-amber-500 shadow' : 'bg-teal-700 text-white border-teal-700 shadow')) : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- AI Needs Summary (Aksen Gold) --}}
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-3.5 space-y-2 shadow-sm">
                        <h4 class="flex items-center gap-1.5 text-[11px] font-extrabold text-amber-800">
                            <span>🧠 Ringkasan Kebutuhan (AI)</span>
                        </h4>
                        <textarea
                            wire:model="aiSummary"
                            rows="4"
                            placeholder="Catatan analisis kebutuhan leads..."
                            class="w-full rounded-xl border border-amber-200 bg-white p-2.5 text-[11px] text-slate-800 placeholder-slate-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20 shadow-inner"
                        ></textarea>
                        <button wire:click="saveAiSummary" class="w-full rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 py-2 text-[11px] font-bold text-white shadow-sm hover:shadow-amber-500/20 transition">
                            Simpan Catatan
                        </button>
                    </div>

                    {{-- Widget Follow-Up Drip Sequence --}}
                    @php
                        $activeEnrollment = \App\Models\FollowUpEnrollment::where('contact_id', $contact->id)
                            ->whereIn('status', ['active', 'paused'])
                            ->with('sequence.steps')
                            ->first();
                    @endphp

                    <div class="rounded-2xl border border-slate-200/80 bg-white p-3.5 space-y-2.5 shadow-sm">
                        <h4 class="flex items-center justify-between text-[11px] font-extrabold text-slate-800">
                            <span>🎯 Follow-Up Drip</span>
                            @if ($activeEnrollment)
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $activeEnrollment->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ strtoupper($activeEnrollment->status) }}
                                </span>
                            @endif
                        </h4>

                        @if ($activeEnrollment)
                            <div class="rounded-xl bg-slate-50 p-2.5 border border-slate-200 space-y-1 text-[11px]">
                                <p class="font-bold text-slate-900 truncate">{{ $activeEnrollment->sequence->name }}</p>
                                <p class="text-slate-500">
                                    Langkah <b>{{ $activeEnrollment->current_step_order }}</b> dari {{ $activeEnrollment->sequence->steps->count() }}
                                </p>
                                <p class="text-slate-400 text-[10px]">
                                    Jadwal: {{ $activeEnrollment->next_scheduled_at ? $activeEnrollment->next_scheduled_at->format('d M, H:i') : '-' }}
                                </p>
                                <div class="flex space-x-1.5 pt-1.5">
                                    @if ($activeEnrollment->status === 'active')
                                        <button wire:click="pauseSequence({{ $activeEnrollment->id }})" class="flex-1 py-1 rounded-lg bg-amber-500 text-white font-bold text-[10px]">Jeda</button>
                                    @else
                                        <button wire:click="resumeSequence({{ $activeEnrollment->id }})" class="flex-1 py-1 rounded-lg bg-emerald-600 text-white font-bold text-[10px]">Lanjutkan</button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="space-y-1.5">
                                <select wire:change="enrollToSequence($event.target.value)" class="w-full rounded-xl border border-slate-200 p-2 text-[11px] bg-slate-50 text-slate-700 font-medium">
                                    <option value="">+ Daftarkan ke Sequence...</option>
                                    @foreach (\App\Models\FollowUpSequence::where('office_id', $office->id)->where('is_active', true)->get() as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->steps()->count() }} Langkah)</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL POPUP: PILIH KONTAK TERSIMPAN (NEW CHAT)                            --}}
    {{-- ========================================================================= --}}
    @if ($showNewChatModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Mulai Chat Leads</h3>
                        <p class="text-[11px] text-slate-400">Pilih kontak tersimpan untuk membuka obrolan</p>
                    </div>
                    <button wire:click="$set('showNewChatModal', false)" class="text-slate-400 hover:text-slate-900 font-bold text-sm">✕</button>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Pilih Akun WhatsApp Pengirim:</label>
                    <select wire:model="newChatChannelId" class="w-full rounded-xl border border-slate-200 p-2 text-xs bg-slate-50 focus:border-teal-500">
                        @foreach ($office->channels()->where('type', 'whatsapp')->where('status', 'connected')->get() as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }} ({{ $ch->identifier }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="contactSearchQuery"
                        placeholder="🔍 Cari nama kontak atau nomor HP..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-3 text-xs text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none"
                    />
                    <svg class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
                    @forelse ($this->availableContacts as $c)
                        @php $color = $this->getAvatarColor($c->name); @endphp
                        <div
                            wire:click="startChatWithContact({{ $c->id }})"
                            class="flex items-center space-x-3 p-2.5 cursor-pointer hover:bg-teal-50/60 transition"
                        >
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl {{ $color }} text-xs font-bold text-white flex-shrink-0">
                                {{ substr($c->name, 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-slate-900 truncate">{{ $c->name }}</h4>
                                <p class="text-[10px] text-slate-400 font-mono">{{ $c->phone_number ?: $c->wa_jid }}</p>
                            </div>
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-bold">
                                {{ ucfirst(str_replace('_', ' ', $c->pipeline_stage)) }}
                            </span>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">
                            Tidak ada kontak yang cocok.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" wire:click="$set('showNewChatModal', false)" class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">Tutup</button>
                </div>
            </div>
        </div>
    @endif

</div>
