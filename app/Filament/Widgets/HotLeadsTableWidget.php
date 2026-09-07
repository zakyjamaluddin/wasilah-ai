<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HotLeadsTableWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Daftar Prospek Panas yang Butuh Tindakan Segera';

    public function table(Table $table): Table
    {
        $office = Filament::getTenant();

        return $table
            ->query(
                Contact::query()
                    ->where('office_id', $office?->id)
                    ->whereIn('pipeline_stage', ['hot_prospect', 'closing'])
                    ->latest('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-user')
                    ->description(fn (Contact $record): string => $record->wa_jid ?: ($record->ig_username ? '@' . $record->ig_username : '-')),


                Tables\Columns\BadgeColumn::make('pipeline_stage')
                    ->label('Status Prospek')
                    ->colors([
                        'danger' => 'hot_prospect',
                        'warning' => 'closing',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hot_prospect' => 'Hot Prospect',
                        'closing' => 'Tahap Closing',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('ai_summary')
                    ->label('Analisis Kebutuhan AI')
                    ->limit(80)
                    ->placeholder('Belum ada ringkasan AI')
                    ->tooltip(fn (Contact $record): ?string => $record->ai_summary)
                    ->wrap(),
                    // buat jadi 2 baris jika panjang, tapi tooltip tetap full text


                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktivitas Terakhir')
                    ->since(),
            ])
            ->actions([
                // 🔥 AKSI 1-KLIK LANGSUNG BUKA OBROLAN DI WORKSPACE
                Action::make('openChat')
                    ->label('Buka Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Contact $record) => route('workspace.crm', ['office' => Filament::getTenant()->slug]), shouldOpenInNewTab: true),
            ])
            ->emptyStateHeading('Belum Ada Prospek Panas')
            ->emptyStateDescription('Ketika status leads diubah menjadi Hot Prospect atau Closing, mereka akan otomatis tampil di sini.');
    }
}
