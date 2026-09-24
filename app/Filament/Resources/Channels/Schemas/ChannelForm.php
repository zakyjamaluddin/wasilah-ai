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

                        // 🔥 DYNAMIC IDENTIFIER FIELD: MENYESUAIKAN PLATFORM
                        TextInput::make('identifier')
                            ->label(fn (Get $get) => match ($get('type')) {
                                'whatsapp'  => 'Session ID WhatsApp (VPS)',
                                'facebook'  => '(Opsional) Facebook Page ID (ID Halaman)',
                                'instagram' => '(Opsional) Instagram Business Account ID',
                                default     => 'Identifier / ID Akun',
                            })
                            ->placeholder(fn (Get $get) => match ($get('type')) {
                                'whatsapp'  => 'Contoh: kantor_jakarta_wa',
                                'facebook'  => 'Contoh: 422099137659003 atau 1178468165341833',
                                'instagram' => 'Contoh: 17841469669611882',
                                default     => 'ID unik saluran',
                            })
                            ->required(fn (Get $get) => $get('type') === 'whatsapp')
                            ->helperText(fn (Get $get) => match ($get('type')) {
                                'whatsapp'  => 'Wajib diisi dengan ID sesi WhatsApp unik di VPS Baileys.',
                                'facebook'  => 'Otomatis terisi saat login Facebook via Composio, atau masukkan ID Halaman Facebook secara manual untuk mengunci Halaman tertentu pada kantor ini.',
                                'instagram' => 'Otomatis terisi saat login Instagram via Composio, atau masukkan ID Akun Instagram secara manual.',
                                default     => null,
                            }),

                        // Textarea::make('credentials.access_token')
                        //     ->label('Page Access Token (Permanent / Long-Lived)')
                        //     ->placeholder('EAAxxxxxx...')
                        //     ->rows(2)
                        //     ->required(fn (Get $get) => in_array($get('type'), ['facebook', 'instagram']))
                        //     ->helperText('Token akses Halaman Facebook atau Instagram Bisnis Anda.'),

                        // // Prompt Tambahan Khusus Facebook First Comment (Opsional)
                        // Textarea::make('credentials.auto_first_comment')
                        //     ->label('Instruksi / Pesan Auto First Comment (Khusus FB Post)')
                        //     ->placeholder('Contoh: Halo Kak! Hubungi WhatsApp kami di wa.me/628xxx untuk info promo terbaru.')
                        //     ->rows(2)
                        //     ->visible(fn (Get $get) => $get('type') === 'facebook')
                        //     ->helperText('Pesan/komentar promosi otomatis yang akan diposting pertama kali saat ada postingan baru di Fanspage FB.'),
                    ])->columns(2),

                Section::make('Kredensial Meta Graph API')
                    ->visible(fn (Get $get) => in_array($get('type'), ['facebook', 'instagram']))
                    ->schema([
                        Textarea::make('credentials.access_token')
                            ->label('Page Access Token (Permanent / Long-Lived)')
                            ->placeholder('EAAxxxxxx...')
                            ->rows(3)
                            ->required(fn (Get $get) => in_array($get('type'), ['facebook', 'instagram']))
                            ->helperText('Token akses Halaman Facebook atau Instagram Bisnis Anda.'),

                        Textarea::make('credentials.auto_first_comment')
                            ->label('Pesan Auto First Comment (Khusus Postingan FB Baru)')
                            ->placeholder('Contoh: Halo Kak! Hubungi WhatsApp kami di wa.me/628xxx untuk info promo terbaru.')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('type') === 'facebook')
                            ->helperText('Otomatis diposting sebagai komentar pertama setiap ada postingan baru di Fanspage.'),
                    ]),
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
