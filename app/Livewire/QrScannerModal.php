<?php

namespace App\Livewire;

use App\Models\Channel;
use App\Services\BaileysService;
use Livewire\Component;

class QrScannerModal extends Component
{
    public $channelId;
    public $qrImage = null;
    public $status = 'loading'; // loading, scanning, connected, error

    public function mount($channelId, BaileysService $baileys)
    {
        $this->channelId = $channelId;
        $channel = Channel::find($this->channelId);

        if ($channel) {
            // Minta Baileys mulai session
            $baileys->startSession($channel->identifier);
            $this->checkStatus($baileys);
        }
    }

    // Fungsi ini akan dipanggil otomatis setiap 2 detik oleh Livewire
    public function checkStatus(BaileysService $baileys)
    {
        $channel = Channel::find($this->channelId);
        if (!$channel) return;

        $response = $baileys->getSessionStatus($channel->identifier);
        $data = $response['data'] ?? [];

        $currentStatus = $data['status'] ?? 'disconnected';

        if ($currentStatus === 'connected') {
            $this->status = 'connected';
            $this->qrImage = null;
            $channel->update(['status' => 'connected']);
        } elseif (!empty($data['qrImage'])) {
            $this->status = 'scanning';
            $this->qrImage = $data['qrImage'];
            $channel->update(['status' => 'scanning']);
        }
    }

    public function render()
    {
        return view('livewire.qr-scanner-modal', [
            'status' => $this->status,
            'qrImage' => $this->qrImage,
        ]);
    }
}
