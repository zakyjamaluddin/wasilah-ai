<?php

namespace App\Filament\Resources\Offices\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class OfficeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kantor Cabang')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kantor / Cabang')
                            ->placeholder('Contoh: Wasilah Travel Cabang Bandung')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug URL Kantor')
                            ->placeholder('cabang-bandung')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Digunakan untuk link akses URL dashboard: /admin/[slug-kantor]'),

                        TextInput::make('phone')
                            ->label('Nomor Telepon / Hotline Kantor')
                            ->tel()
                            ->placeholder('08123456789'),

                        Toggle::make('is_active')
                            ->label('Status Kantor Aktif')
                            ->default(true),

                        Textarea::make('address')
                            ->label('Alamat Lengkap Kantor Cabang')
                            ->placeholder('Jl. Asia Afrika No. 123, Bandung')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columns(1);
    }
}
