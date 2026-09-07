<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\BaileysService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class OmnichannelCrm extends Page
{
    protected static ?string $navigationLabel = 'Omnichannel CRM';
    protected static ?string $title = 'Omnichannel Live CRM';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.omnichannel-crm';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    // State Livewire
    public ?int $selectedConversationId = null;
    public string $searchQuery = '';
    public string $channelFilter = 'all'; // all, whatsapp, fb_dm, ig_dm
    public string $replyText = '';
    public string $pipelineStage = 'lead';
    public ?string $aiSummary = '';

    public static function getNavigationItems(): array
    {
        $office = Filament::getTenant();
        if (!$office) return [];

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url(route('workspace.crm', ['office' => $office->slug]), shouldOpenInNewTab: true),
        ];
    }

    public function mount()
    {
        // Fallback jika ada yang mengakses URL-nya langsung
        $office = Filament::getTenant();
        if ($office) {
            return redirect()->route('workspace.crm', ['office' => $office->slug]);
        }
    }

    // Ambil daftar percakapan milik kantor saat ini
    public function getConversationsQuery()
    {
        $office = Filament::getTenant();

        return Conversation::query()
            ->where('office_id', $office->id)
            ->with(['contact', 'channel', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->when($this->channelFilter !== 'all', fn ($q) => $q->where('channel_type', $this->channelFilter))
            ->when(!empty($this->searchQuery), function ($q) {
                $q->whereHas('contact', function ($cq) {
                    $cq->where('name', 'like', "%{$this->searchQuery}%")
                       ->orWhere('phone_number', 'like', "%{$this->searchQuery}%")
                       ->orWhere('wa_jid', 'like', "%{$this->searchQuery}%");
                });
            })
            ->orderBy('last_message_at', 'desc');
    }

    // Pilih room chat yang diklik di sidebar kiri
    public function selectConversation(int $id)
    {
        $this->selectedConversationId = $id;

        $conv = Conversation::with('contact')->find($id);
        if ($conv) {
            // Reset unread count
            $conv->update(['unread_count' => 0]);
            $this->pipelineStage = $conv->contact->pipeline_stage ?? 'lead';
            $this->aiSummary = $conv->contact->ai_summary ?? '';
        }
    }

    // Toggle On/Off AI Chatbot untuk room ini
    public function toggleBot()
    {
        if (!$this->selectedConversationId) return;

        $conv = Conversation::find($this->selectedConversationId);
        if ($conv) {
            $conv->update(['is_bot_active' => !$conv->is_bot_active]);
            $statusText = $conv->is_bot_active ? 'diaktifkan' : 'dinonaktifkan (Manual CS Takeover)';
            Notification::make()->title("AI Bot {$statusText} untuk chat ini.")->success()->send();
        }
    }

    // Update Status Pipeline Leads (Cold, Warm, Hot, dll) di Kolom Kanan
    public function updatePipeline(string $stage)
    {
        if (!$this->selectedConversationId) return;

        $conv = Conversation::with('contact')->find($this->selectedConversationId);
        if ($conv && $conv->contact) {
            $conv->contact->update(['pipeline_stage' => $stage]);
            $this->pipelineStage = $stage;
            Notification::make()->title("Status prospek diubah menjadi: {$stage}")->success()->send();
        }
    }

    // Simpan Perubahan Ringkasan AI / Catatan Manual
    public function saveAiSummary()
    {
        if (!$this->selectedConversationId) return;

        $conv = Conversation::with('contact')->find($this->selectedConversationId);
        if ($conv && $conv->contact) {
            $conv->contact->update(['ai_summary' => $this->aiSummary]);
            Notification::make()->title('Ringkasan kebutuhan leads berhasil diperbarui!')->success()->send();
        }
    }

    // Kirim Balasan Pesan dari Admin / CS ke WhatsApp
    public function sendReply(BaileysService $baileys)
    {
        if (empty(trim($this->replyText)) || !$this->selectedConversationId) return;

        $conv = Conversation::with(['contact', 'channel'])->find($this->selectedConversationId);
        if (!$conv) return;

        $textToSend = trim($this->replyText);
        $this->replyText = ''; // Kosongkan input bar

        $office = Filament::getTenant();

        // 1. Simpan pesan CS ke Database
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'office_id' => $office->id,
            'user_id' => Auth::id(),
            'sender_type' => 'agent',
            'message_type' => 'text',
            'message_body' => $textToSend,
            'is_read' => true,
        ]);

        $conv->update(['last_message_at' => now()]);

        // 2. Jika Channel WhatsApp, Tembak ke VPS Baileys
        if ($conv->channel_type === 'whatsapp' && $conv->channel) {
            try {
                $targetJid = $conv->contact->wa_jid ?: $conv->contact->phone_number;
                $response = $baileys->sendTextMessage($conv->channel->identifier, $targetJid, $textToSend);

                if (!empty($response['data']['messageId'])) {
                    $msg->update(['external_message_id' => $response['data']['messageId']]);
                }
            } catch (\Exception $e) {
                Notification::make()->title('Gagal mengirim ke WhatsApp: ' . $e->getMessage())->danger()->send();
            }
        }
    }

    // Ambil detail percakapan aktif untuk dirender di UI
    public function getActiveConversationProperty()
    {
        if (!$this->selectedConversationId) return null;
        return Conversation::with(['contact', 'channel', 'messages' => fn ($q) => $q->orderBy('created_at', 'asc')])
            ->find($this->selectedConversationId);
    }
}
