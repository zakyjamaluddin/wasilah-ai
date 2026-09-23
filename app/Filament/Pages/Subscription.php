<?php

namespace App\Filament\Pages;

use App\Models\Office;
use App\Models\SubscriptionOrder;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class Subscription extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;
    protected static string | UnitEnum | null $navigationGroup = 'Setting & Integration';
    
    protected static ?string $navigationLabel = 'Langganan (Subscription)';
    protected static ?string $title = 'Kelola Langganan Kantor';
    protected static ?string $slug = 'subscription';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.subscription';

    // State Modal Checkout Lynk.id
    public bool $showCheckoutModal = false;
    public ?int $selectedOfficeId = null;
    public string $selectedPlan = '1_month'; // '1_month' atau '1_year'

    // State Menunggu Verifikasi Pembayaran (Tab Baru Dibuka)
    public bool $waitingForPayment = false;
    public ?string $activeOrderCode = null;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function getCurrentOfficeProperty(): ?Office
    {
        $tenant = Filament::getTenant();
        return $tenant instanceof Office ? $tenant : null;
    }

    public function getUserOfficesProperty()
    {
        $user = Auth::user();
        if (!$user) return collect();

        if ($user->id === 1 || $user->hasRole('super_admin')) {
            return Office::orderBy('name')->get();
        }

        return $user->offices()->orderBy('name')->get();
    }

    public function openPaymentModal(int $officeId)
    {
        $this->selectedOfficeId = $officeId;
        $this->selectedPlan = '1_month';
        $this->waitingForPayment = false;
        $this->activeOrderCode = null;
        $this->showCheckoutModal = true;
    }

    /**
     * Buka Lynk.id di Tab Baru & Masuk ke Mode Menunggu Pembayaran
     */
    public function proceedToLynkPayment()
    {
        $user = Auth::user();
        $office = Office::find($this->selectedOfficeId);

        if (!$user || !$office) {
            Notification::make()->title('Data kantor tidak valid!')->danger()->send();
            return;
        }

        $amount = $this->selectedPlan === '1_year' ? 300000 : 37000;
        $orderCode = 'ORD-' . strtoupper(Str::random(10));

        // 1. Simpan Order Pending
        SubscriptionOrder::create([
            'order_code'       => $orderCode,
            'user_id'          => $user->id,
            'office_id'        => $office->id,
            'customer_email'   => strtolower(trim($user->email)),
            'plan_type'        => $this->selectedPlan,
            'amount'           => $amount,
            'status'           => 'pending',
            'payment_provider' => 'lynkid',
        ]);

        $this->activeOrderCode = $orderCode;
        $this->waitingForPayment = true;

        // 2. Ambil Link Lynk.id
        $paymentUrl = $this->selectedPlan === '1_year'
            ? config('services.lynkid.url_1_year')
            : config('services.lynkid.url_1_month');

        // 3. Buka Link Pembayaran di TAB BARU (Window Open)
        $this->js("window.open('{$paymentUrl}', '_blank');");

        Notification::make()
            ->title('Tab Pembayaran Dibuka')
            ->body("Silakan selesaikan pembayaran di tab Lynk.id yang baru terbuka.")
            ->info()
            ->send();
    }

    /**
     * Polling Otomatis Setiap 3 Detik: Deteksi jika Webhook Email sudah memvalidasi pembayaran
     */
    public function checkPaymentStatus()
    {
        if (!$this->waitingForPayment || !$this->activeOrderCode) {
            return;
        }

        $order = SubscriptionOrder::where('order_code', $this->activeOrderCode)->first();

        if ($order && $order->status === 'paid') {
            $this->waitingForPayment = false;
            $this->showCheckoutModal = false;
            $this->activeOrderCode = null;

            Notification::make()
                ->title('🎉 Pembayaran Berhasil Terverifikasi!')
                ->body("Langganan kantor <b>{$order->office->name}</b> telah aktif dan diperpanjang.")
                ->success()
                ->persistent()
                ->send();
        }
    }
}

