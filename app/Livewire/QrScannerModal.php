<?php

namespace App\Livewire;

use App\Models\Channel;
use App\Services\BaileysService;
use Illuminate\Support\Str;
use Livewire\Component;

class QrScannerModal extends Component
{
    public $channelId;
    public $qrImage = null;
    public $status = 'loading'; // loading, scanning, connected, error
    public $errorMessage = null;
    public $attempts = 0;

    public function mount($channelId, BaileysService $baileys)
    {
        $this->channelId = $channelId;
        $this->initSession($baileys);
    }

    public function initSession(BaileysService $baileys)
    {
        $this->status = 'loading';
        $this->errorMessage = null;
        $this->attempts = 0;

        $channel = Channel::find($this->channelId);

        if (!$channel) {
            $this->status = 'error';
            $this->errorMessage = 'Data channel tidak ditemukan.';
            return;
        }

        // Pastikan identifier tidak kosong
        if (empty(trim((string) $channel->identifier))) {
            $identifier = 'channel_' . $channel->id . '_' . Str::random(6);
            $channel->update(['identifier' => $identifier]);
            $channel->identifier = $identifier;
        }

        // Minta Baileys mulai session
        $startRes = $baileys->startSession($channel->identifier);
        if (isset($startRes['status']) && $startRes['status'] === 'error') {
            $this->status = 'error';
            $this->errorMessage = $startRes['message'] ?? 'Gagal menghubungi server Baileys WhatsApp di VPS.';
            return;
        }

        $this->checkStatus($baileys);
    }

    // Fungsi ini akan dipanggil otomatis setiap 2 detik oleh Livewire
    public function checkStatus(BaileysService $baileys)
    {
        $channel = Channel::find($this->channelId);
        if (!$channel) return;

        $this->attempts++;

        $response = $baileys->getSessionStatus($channel->identifier);
        $data = $response['data'] ?? [];

        $currentStatus = $data['status'] ?? null;

        if ($currentStatus === 'connected') {
            $this->status = 'connected';
            $this->qrImage = null;
            $this->errorMessage = null;
            $channel->update(['status' => 'connected']);
        } elseif (!empty($data['qrImage'])) {
            $this->status = 'scanning';
            $this->qrImage = $data['qrImage'];
            $this->errorMessage = null;
            $channel->update(['status' => 'scanning']);
        } elseif (isset($response['status']) && $response['status'] === 'error') {
            $this->status = 'error';
            $this->errorMessage = $response['message'] ?? 'Gagal mengambil status sesi dari server Baileys.';
        } elseif ($this->attempts >= 15 && empty($this->qrImage)) {
            $this->status = 'error';
            $this->errorMessage = 'Waktu tunggu pembuatan QR melebihi 30 detik. Pastikan server web dapat menjangkau port 3100 VPS.';
        }
    }

    public function retry(BaileysService $baileys)
    {
        $this->initSession($baileys);
    }

    public function render()
    {
        return view('livewire.qr-scanner-modal', [
            'status' => $this->status,
            'qrImage' => $this->qrImage,
            'errorMessage' => $this->errorMessage,
        ]);
    }
}
