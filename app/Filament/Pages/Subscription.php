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

    // State Modal Checkout Lynk.id
    public bool $showCheckoutModal = false;
    public ?int $selectedOfficeId = null;
    public string $selectedPlan = '1_month'; // '1_month' atau '1_year'

    protected string $view = 'filament.pages.subscription';

    /**
     * Halaman ini SELALU BISA DIAKSES oleh tenant dalam status apapun (free/active/expiring/inactive).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Dapatkan data kantor yang sedang aktif dibuka.
     */
    public function getCurrentOfficeProperty(): ?Office
    {
        $tenant = Filament::getTenant();
        return $tenant instanceof Office ? $tenant : null;
    }

    /**
     * Dapatkan semua kantor yang dikelola oleh user yang sedang login.
     */
    public function getUserOfficesProperty()
    {
        $user = Auth::user();
        if (!$user) return collect();

        if ($user->id === 1 || $user->hasRole('super_admin')) {
            return Office::orderBy('name')->get();
        }

        return $user->offices()->orderBy('name')->get();
    }

    /**
     * Aksi pemicu tombol perpanjang langganan.
     */
    public function renewSubscription(int $officeId)
    {
        $targetOffice = Office::find($officeId);
        if (!$targetOffice) return;

        Notification::make()
            ->title('🚀 Menuju Pembayaran')
            ->body("Permintaan perpanjangan untuk kantor <b>{$targetOffice->name}</b> sedang diproses.")
            ->info()
            ->send();

        // Nantinya bisa dialihkan ke rute checkout payment gateway
        // return redirect()->route('checkout.show', ['office' => $targetOffice->slug]);
    }


    /**
     * Buka Modal Pilihan Paket Pembayaran
     */
    public function openPaymentModal(int $officeId)
    {
        $this->selectedOfficeId = $officeId;
        $this->selectedPlan = '1_month';
        $this->showCheckoutModal = true;
    }

    /**
     * Eksekusi Checkout: Buat Order Pending & Redirect ke Lynk.id
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

        // 1. Simpan Niat Bayar (Pending Order) ke Database
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

        // 2. Ambil Link Lynk.id dari config/.env
        $paymentUrl = $this->selectedPlan === '1_year'
            ? config('services.lynkid.url_1_year')
            : config('services.lynkid.url_1_month');

        $this->showCheckoutModal = false;

        Notification::make()
            ->title('🚀 Menuju Halaman Pembayaran Lynk.id')
            ->body("Pastikan Anda menggunakan email <b>{$user->email}</b> saat checkout di Lynk.id agar sistem dapat mengaktifkan kantor <b>{$office->name}</b> secara otomatis.")
            ->success()
            ->persistent()
            ->send();

        // 3. Arahkan Pengguna ke Lynk.id
        return redirect()->away($paymentUrl);
    }
}
