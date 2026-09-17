<?php

namespace App\Filament\Resources\ComposioAccounts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComposioAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kelola Token Akun Composio')
                    ->description('Masukkan API Key dari Composio.dev untuk integrasi Facebook & Instagram.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Akun / Label')
                            ->placeholder('Contoh: Akun Composio 1 (zaky@email.com)')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('api_key')
                            ->label('Composio API Key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Dapatkan API Key dari https://app.composio.dev/settings'),
                        TextInput::make('webhook_secret')
                            ->label('Composio Webhook Secret')
                            ->password()
                            ->revealable()
                            ->placeholder('whsec_...')
                            ->helperText('Dapatkan Secret ini setelah Anda membuat Webhook di dashboard Composio.')
                            ->nullable(),
                        TextInput::make('max_offices')
                            ->label('Batas Maksimal Kantor')
                            ->numeric()
                            ->default(4)
                            ->required()
                            ->minValue(1)
                            ->helperText('Default: 4 kantor per 1 akun Composio.'),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->placeholder('Catatan internal (opsional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
