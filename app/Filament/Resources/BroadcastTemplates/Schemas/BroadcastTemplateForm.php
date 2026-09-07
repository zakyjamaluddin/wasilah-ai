<?php

namespace App\Filament\Resources\BroadcastTemplates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BroadcastTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Draf Pesan Broadcast')
                    ->description('Gunakan tag {{name}} untuk menyapa nama customer secara personal.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Template')
                            ->placeholder('Contoh: Promo Diskon 50% Gajian')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('media_url')
                            ->label('Gambar Promosi (Opsional)')
                            ->image()
                            ->directory('broadcast-media')
                            ->disk('public'),
                        Textarea::make('message_template')
                            ->label('Isi Pesan Promosi')
                            ->placeholder("Halo {{name}}! 👋\nAda promo spesial khusus hari ini diskon 50% untuk Anda...")
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columns(1);
    }
}
