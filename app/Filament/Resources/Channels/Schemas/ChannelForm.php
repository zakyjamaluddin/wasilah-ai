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
                Section::make('Informasi Saluran')
                    ->description('Pilih platform dan beri nama saluran komunikasi kantor Anda.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Channel')
                            ->placeholder('Contoh: CS Utama Facebook Page / WA CS 1')
                            ->required(),

                        Select::make('type')
                            ->label('Platform Channel')
                            ->options([
                                'whatsapp'  => 'WhatsApp (Unofficial Baileys)',
                                'facebook'  => 'Facebook Page (Composio AI)',
                                'instagram' => 'Instagram Business (Composio AI)',
                            ])
                            ->default('whatsapp')
                            ->reactive()
                            ->required(),

                        // Session ID Baileys HANYA wajib diisi jika tipe WhatsApp
                        TextInput::make('identifier')
                            ->label('Session ID WhatsApp (VPS)')
                            ->placeholder('Contoh: kantor_jakarta_wa')
                            ->visible(fn (Get $get) => $get('type') === 'whatsapp')
                            ->required(fn (Get $get) => $get('type') === 'whatsapp')
                            ->helperText('ID unik sesi WhatsApp di VPS Baileys.'),

                        // Prompt Tambahan Khusus Facebook First Comment (Opsional)
                        Textarea::make('credentials.auto_first_comment')
                            ->label('Instruksi / Pesan Auto First Comment (Khusus FB Post)')
                            ->placeholder('Contoh: Halo Kak! Hubungi WhatsApp kami di wa.me/628xxx untuk info promo terbaru.')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('type') === 'facebook')
                            ->helperText('Pesan/komentar promosi otomatis yang akan diposting pertama kali saat ada postingan baru di Fanspage FB.'),
                    ])->columns(2),

                Section::make('🤖 Pengaturan Otomasi AI Chatbot')
                    ->schema([
                        Toggle::make('is_bot_enabled')
                            ->label('Aktifkan AI Chatbot Otomatis')
                            ->helperText('Jika aktif, Google Gemini AI akan otomatis menjawab pesan/komentar masuk.')
                            ->default(true),

                        Toggle::make('sync_groups')
                            ->label('Tampilkan Obrolan Grup WhatsApp di OmniChat')
                            ->helperText('Jika dinonaktifkan (default), OmniChat hanya menampilkan chat personal 1-on-1.')
                            ->visible(fn (Get $get) => $get('type') === 'whatsapp')
                            ->default(false),
                    ]),

                Section::make('⏰ Jadwal Jam Aktif Bot (Opsional)')
                    ->description('Atur kapan AI bot diperbolehkan membalas pesan secara otomatis.')
                    ->schema([
                        Select::make('bot_schedule_type')
                            ->label('Mode Penjadwalan')
                            ->options([
                                'always'               => '24 Jam Nonstop (Selalu Aktif)',
                                'outside_office_hours' => 'Hanya Aktif di Luar Jam Kerja CS (Malam & Libur)',
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