<?php

namespace App\Filament\Resources\Channels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Channel')
                    ->placeholder('Contoh: WA CS Utama Jakarta')
                    ->required(),
                Select::make('type')
                    ->label('Platform Channel')
                    ->options([
                        'whatsapp' => 'WhatsApp (Unofficial Baileys)',
                        'facebook' => 'Facebook Page (Meta Graph)',
                        'instagram' => 'Instagram Business (Meta Graph)',
                    ])
                    ->default('whatsapp')
                    ->reactive()
                    ->required(),

                TextInput::make('identifier')
                    ->label(fn (Get $get) => $get('type') === 'whatsapp' ? 'Session ID Baileys' : 'Page ID / Instagram Business ID')
                    ->placeholder(fn (Get $get) => $get('type') === 'whatsapp' ? 'kantor_jakarta' : '102938475612345')
                    ->required(),

                // 🔥 KHUSUS FACEBOOK & INSTAGRAM
                Section::make('Kredensial Meta Graph API')
                    ->visible(fn (Get $get) => in_array($get('type'), ['facebook', 'instagram']))
                    ->schema([
                        Textarea::make('credentials.access_token')
                            ->label('Page Access Token (Permanent)')
                            ->placeholder('EAAxxxxxx...')
                            ->rows(3)
                            ->required(fn (Get $get) => in_array($get('type'), ['facebook', 'instagram'])),
                        Textarea::make('credentials.auto_first_comment')
                            ->label('Pesan Auto First Comment (Khusus FB Post)')
                            ->placeholder('Contoh: Halo! Dapatkan diskon 50% khusus hari ini dengan klik link wa.me/628xxx')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('type') === 'facebook')
                            ->helperText('Otomatis diposting sebagai komentar pertama setiap ada postingan baru di Fanspage.'),
                    ]),
                Toggle::make('is_bot_enabled')
                    ->label('Aktifkan AI Chatbot Otomatis')
                    ->default(true),

                Section::make('⏰ Jadwal Jam Aktif Bot (Opsional)')
                    ->description('Atur kapan bot boleh membalas pesan secara otomatis.')
                    ->schema([
                        Select::make('bot_schedule_type')
                            ->label('Mode Penjadwalan')
                            ->options([
                                'always' => '24 Jam Nonstop (Selalu Aktif)',
                                'outside_office_hours' => 'Hanya Aktif di Luar Jam Kerja (Malam & Libur)',
                            ])
                            ->default('always'),
                        TimePicker::make('office_start_time')
                            ->label('Jam Masuk CS (Siang)')
                            ->default('08:00')
                            ->seconds(false),
                        TimePicker::make('office_end_time')
                            ->label('Jam Pulang CS (Malam)')
                            ->default('17:00')
                            ->seconds(false),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ])->columns(1);
    }
}
