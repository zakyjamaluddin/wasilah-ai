<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $office = Filament::getTenant();
        if (!$office) return [];

        // 1. Total Database Kontak
        $totalContacts = Contact::where('office_id', $office->id)->count();

        // 2. Prospek Panas Siap Closing (Hot Prospect & Closing)
        $hotLeads = Contact::where('office_id', $office->id)
            ->whereIn('pipeline_stage', ['hot_prospect', 'closing'])
            ->count();

        // 3. Deals Won (Closing Sukses)
        $wonDeals = Contact::where('office_id', $office->id)
            ->where('pipeline_stage', 'won')
            ->count();

        return [
            Stat::make('Total Database Leads', number_format($totalContacts))
                ->description('Seluruh prospek terdaftar')
                ->descriptionIcon('heroicon-o-users')
                ->color('info')
                ->chart([7, 12, 18, 25, 30, $totalContacts]),

            Stat::make('Prospek Panas (Hot Leads)', number_format($hotLeads))
                ->description('Siap closing / butuh tindakan')
                ->descriptionIcon('heroicon-o-fire')
                ->color('warning')
                ->chart([2, 5, 8, 12, 15, $hotLeads]),

            Stat::make('Closing Sukses (Deals Won)', number_format($wonDeals))
                ->description('Penjualan berhasil dicapai')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('success')
                ->chart([1, 4, 9, 15, 22, $wonDeals]),
        ];
    }
}
