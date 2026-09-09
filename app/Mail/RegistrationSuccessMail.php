<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $loginUrl;

    public function __construct(Order $order, string $loginUrl)
    {
        $this->order = $order;
        $this->loginUrl = $loginUrl;
    }

    public function build()
    {
        return $this->subject("🎉 Pembayaran Diterima! Akun Wasilah AI Anda Telah Aktif")
                    ->view('emails.registration-success');
    }
}
