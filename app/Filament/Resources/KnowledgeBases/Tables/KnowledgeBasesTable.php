<?php

namespace App\Filament\Resources\KnowledgeBases\Tables;

use App\Models\KnowledgeBase;
use App\Services\KnowledgeScraperService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KnowledgeBasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Pengetahuan')
                    ->searchable()
                    ->weight('bold'),
                BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'text_doc',
                        'warning' => 'faq',
                        'success' => 'dynamic_url',
                    ]),
                BadgeColumn::make('platform')
                    ->label('Platform'),
                IconColumn::make('is_active')
                    ->label('Status Aktif')
                    ->boolean(),
                TextColumn::make('last_synced_at')
                    ->label('Terakhir Sync Web')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Manual Text'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // 🔥 ACTION SYNC / SCRAPE ULANG DARI WEB SECARA INSTAN
                Action::make('syncWeb')
                    ->label('Sync Ulang Web')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (KnowledgeBase $record) => $record->type === 'dynamic_url' && !empty($record->source_url))
                    ->action(function (KnowledgeBase $record, KnowledgeScraperService $scraper) {
                        $scrapedText = $scraper->scrapeUrl($record->source_url);
                        if ($scrapedText) {
                            $record->update([
                                'content' => $scrapedText,
                                'last_synced_at' => now(),
                            ]);
                            Notification::make()->title('Website berhasil di-scrape ulang! AI kini memiliki data terbaru.')->success()->send();
                        } else {
                            Notification::make()->title('Gagal men-scrape URL website')->danger()->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
