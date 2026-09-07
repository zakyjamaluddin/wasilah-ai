<?php

namespace App\Filament\Resources\KnowledgeBases\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class KnowledgeBaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Materi Basis Pengetahuan AI')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Materi / Produk')
                            ->placeholder('Contoh: Informasi Harga & SOP Layanan')
                            ->required(),
                        Select::make('platform')
                            ->label('Target Platform')
                            ->options([
                                'all' => 'Semua Platform (WA, FB, IG)',
                                'whatsapp' => 'Khusus WhatsApp Saja',
                                'facebook' => 'Khusus Facebook Saja',
                                'instagram' => 'Khusus Instagram Saja',
                            ])
                            ->default('all')
                            ->required(),
                        Select::make('type')
                            ->label('Tipe Sumber Materi')
                            ->options([
                                'text_doc' => 'Teks Dokumen Bebas',
                                'faq' => 'Tanya Jawab (FAQ)',
                                'dynamic_url' => '🌐 Dynamic URL (Website HTML Scraping)',
                            ])
                            ->default('text_doc')
                            ->reactive()
                            ->required(),
                        TextInput::make('source_url')
                            ->label('Link URL Website')
                            ->placeholder('https://tokoanda.com/katalog-produk')
                            ->url()
                            ->visible(fn (Get $get) => $get('type') === 'dynamic_url')
                            ->helperText('Sistem akan otomatis mengekstrak seluruh informasi dari website ini.'),
                        Textarea::make('content')
                            ->label('Konten Pengetahuan')
                            ->placeholder('Tuliskan detail produk, harga, nomor rekening, alamat kantor, dll...')
                            ->rows(8)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktifkan Materi Ini untuk AI')
                            ->default(true),
                    ])->columns(2),
            ])->columns(1);
    }
}
