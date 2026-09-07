<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class GreetingOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.greeting-overview-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        $office = Filament::getTenant();
        $user = Auth::user();

        // Waktu dinamis (Pagi / Siang / Sore / Malam)
        $hour = now()->timezone('Asia/Jakarta')->hour;
        if ($hour >= 4 && $hour < 11) $greeting = 'Selamat Pagi';
        elseif ($hour >= 11 && $hour < 15) $greeting = 'Selamat Siang';
        elseif ($hour >= 15 && $hour < 18) $greeting = 'Selamat Sore';
        else $greeting = 'Selamat Malam';

        $activeWaChannel = $office ? $office->channels()->where('type', 'whatsapp')->where('status', 'connected')->first() : null;

        return [
            'greeting' => $greeting,
            'user' => $user,
            'office' => $office,
            'activeWaChannel' => $activeWaChannel,
            'workspaceUrl' => $office ? route('workspace.crm', ['office' => $office->slug]) : '#',
        ];
    }
}
