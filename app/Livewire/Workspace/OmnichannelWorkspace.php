<?php

namespace App\Livewire\Workspace;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\FollowUpEnrollment;
use App\Models\FollowUpSequence;
use App\Models\Message;
use App\Models\Office;
use App\Services\BaileysService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class OmnichannelWorkspace extends Component
{
    use WithFileUploads;

    public Office $office;
    public ?int $selectedConversationId = null;
    public string $searchQuery = '';
    public string $tabFilter = 'all';
    public string $replyText = '';
    public $attachment = null;
    public string $pipelineStage = 'lead';
    public ?string $aiSummary = '';

    // State Mobile Responsive: 'list' (tampil daftar chat) atau 'chat' (tampil isi obrolan)
    public string $mobileView = 'list';

    // State Modal Mulai Chat Baru
    public bool $showNewChatModal = false;
    public string $newChatType = 'phone'; // 'phone' atau 'group'
    public string $newChatPhone = '';
    public string $newChatName = '';
    public string $newChatGroupId = '';
    public ?int $newChatChannelId = null;

    // State Modal Mulai Chat Baru (Pilih dari Kontak Tersimpan)
    public string $contactSearchQuery = '';

    public function openNewChatModal()
    {
        $this->contactSearchQuery = '';
        $this->newChatChannelId = $this->office->channels()->where('type', 'whatsapp')->where('status', 'connected')->first()?->id;
        $this->showNewChatModal = true;
    }

    // Ambil daftar kontak tersimpan di kantor ini (Hanya kontak personal)
    public function getAvailableContactsProperty()
    {
        return Contact::where('office_id', $this->office->id)
            ->where('wa_jid', 'not like', '%@g.us%') // Hanya kontak personal
            ->when(!empty($this->contactSearchQuery), function ($q) {
                $q->where('name', 'like', "%{$this->contactSearchQuery}%")
                  ->orWhere('phone_number', 'like', "%{$this->contactSearchQuery}%")
                  ->orWhere('wa_jid', 'like', "%{$this->contactSearchQuery}%");
            })
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get();
    }

    // Mulai chat dengan kontak yang dipilih dari daftar
    public function startChatWithContact(int $contactId)
    {
        $channel = Channel::find($this->newChatChannelId);
        if (!$channel) {
            $this->dispatch('notify', 'Silakan pilih channel WhatsApp aktif terlebih dahulu!');
            return;
        }

        $contact = Contact::find($contactId);
        if (!$contact) return;

        // Cari atau buat room percakapan
        $conversation = Conversation::firstOrCreate(
            [
                'office_id' => $this->office->id,
                'channel_id' => $channel->id,
                'contact_id' => $contact->id,
            ],
            [
                'channel_type' => 'whatsapp',
                'is_bot_active' => $channel->is_bot_enabled,
                'last_message_at' => now(),
            ]
        );

        $this->showNewChatModal = false;
        $this->selectConversation($conversation->id);
    }

    public function createNewChat()
    {
        $channel = Channel::find($this->newChatChannelId);
        if (!$channel) {
            $this->dispatch('notify', 'Silakan pilih channel WhatsApp aktif terlebih dahulu!');
            return;
        }

        $targetJid = '';
        $contactName = '';

        // 1. Jika Target Nomor HP Personal
        if ($this->newChatType === 'phone') {
            $cleanPhone = preg_replace('/\D/', '', $this->newChatPhone);
            if (empty($cleanPhone)) return;
            if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);

            $targetJid = $cleanPhone . '@s.whatsapp.net';
            $contactName = !empty(trim($this->newChatName)) ? trim($this->newChatName) : "+{$cleanPhone}";

            $contact = Contact::firstOrCreate(
                ['office_id' => $this->office->id, 'wa_jid' => $targetJid],
                ['name' => $contactName, 'phone_number' => $cleanPhone, 'pipeline_stage' => 'lead']
            );
        }
        // 2. Jika Target Grup WA
        else {
            if (empty($this->newChatGroupId)) return;
            $targetJid = $this->newChatGroupId;
            $contactName = !empty(trim($this->newChatName)) ? trim($this->newChatName) : 'Grup WhatsApp';

            $contact = Contact::firstOrCreate(
                ['office_id' => $this->office->id, 'wa_jid' => $targetJid],
                ['name' => $contactName, 'pipeline_stage' => 'lead']
            );
        }

        // Buat Room Obrolan
        $conversation = Conversation::firstOrCreate(
            [
                'office_id' => $this->office->id,
                'channel_id' => $channel->id,
                'contact_id' => $contact->id,
            ],
            [
                'channel_type' => 'whatsapp',
                'is_bot_active' => $this->newChatType === 'phone' ? $channel->is_bot_enabled : false,
                'last_message_at' => now(),
            ]
        );

        $this->showNewChatModal = false;
        $this->selectConversation($conversation->id);
    }

    public function mount(Office $office)
    {
        $this->office = $office;
        $firstConv = $this->getConversations()->first();
        if ($firstConv && !request()->header('User-Agent-Mobile')) {
            $this->selectConversation($firstConv->id, false);
        }
    }

    public function getConversations()
    {
        return Conversation::query()
            ->where('office_id', $this->office->id)
            ->with(['contact', 'channel', 'latestMessage'])
            // 🔥 5 TAB FILTER OMNICHANNEL CERDAS
            ->when($this->tabFilter === 'whatsapp', function ($q) {
                $q->where('channel_type', 'whatsapp')
                  ->whereHas('contact', fn ($cq) => $cq->where('wa_jid', 'not like', '%@g.us%'));
            })
            ->when($this->tabFilter === 'facebook', function ($q) {
                $q->whereIn('channel_type', ['fb_dm', 'fb_comment']);
            })
            ->when($this->tabFilter === 'instagram', function ($q) {
                $q->whereIn('channel_type', ['ig_dm', 'ig_comment']);
            })
            ->when($this->tabFilter === 'group', function ($q) {
                $q->where('channel_type', 'whatsapp')
                  ->whereHas('contact', fn ($cq) => $cq->where('wa_jid', 'like', '%@g.us%'));
            })
            ->when(!empty($this->searchQuery), function ($q) {
                $q->whereHas('contact', function ($cq) {
                    $cq->where('name', 'like', "%{$this->searchQuery}%")
                       ->orWhere('phone_number', 'like', "%{$this->searchQuery}%")
                       ->orWhere('wa_jid', 'like', "%{$this->searchQuery}%")
                       ->orWhere('ig_username', 'like', "%{$this->searchQuery}%");
                });
            })
            ->orderBy('last_message_at', 'desc')
            ->get();
    }

    public function selectConversation(int $id, bool $switchMobileView = true)
    {
        $this->selectedConversationId = $id;
        if ($switchMobileView) {
            $this->mobileView = 'chat'; // Di HP: Langsung buka ruang chat
        }

        $conv = Conversation::with('contact')->find($id);
        if ($conv) {
            $conv->update(['unread_count' => 0]);
            $this->pipelineStage = $conv->contact->pipeline_stage ?? 'lead';
            $this->aiSummary = $conv->contact->ai_summary ?? '';
        }
    }

    public function backToMobileList()
    {
        $this->mobileView = 'list'; // Di HP: Kembali ke daftar chat
    }

    public function toggleBot()
    {
        if (!$this->selectedConversationId) return;
        $conv = Conversation::find($this->selectedConversationId);
        if ($conv) $conv->update(['is_bot_active' => !$conv->is_bot_active]);
    }

    public function updatePipeline(string $stage)
    {
        if (!$this->selectedConversationId) return;
        $conv = Conversation::with('contact')->find($this->selectedConversationId);
        if ($conv && $conv->contact) {
            $conv->contact->update(['pipeline_stage' => $stage]);
            $this->pipelineStage = $stage;
        }
    }

    public function saveAiSummary()
    {
        if (!$this->selectedConversationId) return;
        $conv = Conversation::with('contact')->find($this->selectedConversationId);
        if ($conv && $conv->contact) {
            $conv->contact->update(['ai_summary' => $this->aiSummary]);
        }
    }

    public function sendReply(BaileysService $baileys, \App\Services\MetaGraphService $meta)
    {
        if ((empty(trim($this->replyText)) && !$this->attachment) || !$this->selectedConversationId) return;

        $conv = Conversation::with(['contact', 'channel'])->find($this->selectedConversationId);
        if (!$conv) return;

        $textToSend = trim($this->replyText);
        $this->replyText = ''; // Kosongkan input bar

        $mediaUrl = null;
        $messageType = 'text';
        $mediaBase64 = null;
        $mimeType = 'image/jpeg';
        $fileName = null;

        // 1. Proses Lampiran Attachment jika ada
        if ($this->attachment) {
            try {
                $mimeType = $this->attachment->getMimeType() ?: 'application/octet-stream';
                $fileName = $this->attachment->getClientOriginalName();
                $fileContents = file_get_contents($this->attachment->getRealPath());
                $mediaBase64 = base64_encode($fileContents);
                $path = $this->attachment->store('media', 'public');
                $mediaUrl = Storage::url($path);

                if (str_contains($mimeType, 'video')) $messageType = 'video';
                elseif (str_contains($mimeType, 'image')) $messageType = 'image';
                else $messageType = 'document';
            } catch (\Exception $e) {}

            $this->reset('attachment');
        }

        // 2. Simpan Pesan CS ke Database CRM
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'office_id' => $this->office->id,
            'user_id' => Auth::id(),
            'sender_type' => 'agent',
            'message_type' => $messageType,
            'message_body' => $textToSend,
            'media_url' => $mediaUrl,
            'is_read' => true,
        ]);

        $conv->update(['last_message_at' => now()]);

        $channel = $conv->channel;
        if (!$channel) return;

        $accessToken = $channel->credentials['access_token'] ?? '';

        // =========================================================================
        // 🎯 EKSEKUSI PENGIRIMAN BALASAN CS KE SALURAN MASING-MASING
        // =========================================================================

        // A. JIKA WHATSAPP
        if ($conv->channel_type === 'whatsapp') {
            try {
                $targetJid = $conv->contact->wa_jid ?: $conv->contact->phone_number;

                if ($mediaBase64) {
                    $response = $baileys->sendMediaMessage(
                        $channel->identifier,
                        $targetJid,
                        $mediaBase64,
                        $textToSend,
                        $mimeType,
                        $fileName
                    );
                } else {
                    $response = $baileys->sendTextMessage($channel->identifier, $targetJid, $textToSend);
                }

                if (!empty($response['data']['messageId'])) {
                    $msg->update(['external_message_id' => $response['data']['messageId']]);
                }
            } catch (\Exception $e) {}
        }

        // B. JIKA FACEBOOK MESSENGER DM
        elseif ($conv->channel_type === 'fb_dm' && !empty($accessToken)) {
            try {
                $recipientPsid = $conv->contact->fb_user_id;
                if ($recipientPsid) {
                    // 🔥 Kirim Teks dan URL Media ke Messenger
                    $meta->sendFacebookMessengerReply($accessToken, $recipientPsid, $textToSend, $mediaUrl, $messageType);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("[CS Reply FB DM Error] " . $e->getMessage());
            }
        }

        // C. JIKA INSTAGRAM DM
        elseif ($conv->channel_type === 'ig_dm' && !empty($accessToken)) {
            try {
                $recipientIgid = $conv->contact->fb_user_id;
                if ($recipientIgid) {
                    // 🔥 Kirim Teks dan URL Media ke Instagram DM
                    $meta->sendInstagramDmReply($accessToken, $recipientIgid, $textToSend, $mediaUrl, $messageType);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("[CS Reply IG DM Error] " . $e->getMessage());
            }
        }

        // D. JIKA KOMENTAR POSTINGAN FB / IG
        elseif ($conv->channel_type === 'fb_comment' && !empty($accessToken)) {
            try {
                $lastCustomerMsg = $conv->messages()->where('sender_type', 'customer')->latest('id')->first();
                if ($lastCustomerMsg && $lastCustomerMsg->external_message_id) {
                    $meta->replyToFacebookComment($accessToken, $lastCustomerMsg->external_message_id, $textToSend);
                }
            } catch (\Exception $e) {}
        } elseif ($conv->channel_type === 'ig_comment' && !empty($accessToken)) {
            try {
                $lastCustomerMsg = $conv->messages()->where('sender_type', 'customer')->latest('id')->first();
                if ($lastCustomerMsg && $lastCustomerMsg->external_message_id) {
                    $meta->replyToInstagramComment($accessToken, $lastCustomerMsg->external_message_id, $textToSend);
                }
            } catch (\Exception $e) {}
        }
    }

    public function getAvatarColor(string $string): string
    {
        $colors = [
            'bg-emerald-600', 'bg-indigo-600', 'bg-rose-600', 'bg-amber-600',
            'bg-violet-600', 'bg-cyan-600', 'bg-fuchsia-600', 'bg-teal-600',
            'bg-orange-600', 'bg-sky-600'
        ];
        $index = abs(crc32($string)) % count($colors);
        return $colors[$index];
    }

    public function getActiveConversationProperty()
    {
        if (!$this->selectedConversationId) return null;
         return Conversation::with([
            'contact',
            'channel',
            // 🔥 Ambil kolom penting saja & urutkan berdasarkan ID (Auto-Index Primary Key)
            'messages' => fn ($q) => $q->select('id', 'conversation_id', 'sender_type', 'message_type', 'message_body', 'media_url', 'created_at')
                                       ->orderBy('id', 'asc')
        ])->find($this->selectedConversationId);
    }

    public function render()
    {
        return view('livewire.workspace.omnichannel-workspace', [
            'conversations' => $this->getConversations(),
            'active' => $this->activeConversation,
        ])->layout('layouts.workspace');
    }

    public function enrollToSequence(int $sequenceId)
    {
        if (!$this->selectedConversationId) return;
        $conv = Conversation::with('contact')->find($this->selectedConversationId);
        $seq = FollowUpSequence::with('steps')->find($sequenceId);

        if ($conv && $seq) {
            app(\App\Services\FollowUpEngineService::class)->enroll($conv->contact, $seq, $conv);
            $this->dispatch('notify', "Kontak berhasil didaftarkan ke sequence: {$seq->name}");
        }
    }

    public function pauseSequence(int $enrollmentId)
    {
        $enr = FollowUpEnrollment::find($enrollmentId);
        if ($enr) {
            $enr->update(['status' => 'paused']);
        }
    }

    public function resumeSequence(int $enrollmentId)
    {
        $enr = FollowUpEnrollment::find($enrollmentId);
        if ($enr) {
            $enr->update(['status' => 'active']);
        }
    }
}
