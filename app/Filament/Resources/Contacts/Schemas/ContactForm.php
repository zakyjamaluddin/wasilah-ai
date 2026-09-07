<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kontak Leads')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Nomor WhatsApp / HP')
                            ->tel(),
                        TextInput::make('wa_jid')
                            ->label('WhatsApp JID / LID')
                            ->helperText('Otomatis terisi jika nomor menggunakan ID privasi'),
                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email(),
                        Select::make('pipeline_stage')
                            ->label('Status Prospek (Pipeline)')
                            ->options([
                                'lead' => 'Baru Masuk (Lead)',
                                'cold_prospect' => 'Cold Prospect',
                                'warm_prospect' => 'Warm Prospect',
                                'hot_prospect' => 'Hot Prospect 🔥',
                                'closing' => 'Tahap Closing',
                                'won' => 'Closing Sukses (Won) 🎉',
                                'lost' => 'Batal (Lost)',
                            ])
                            ->default('lead')
                            ->required(),
                        Select::make('groups')
                            ->label('Grup Kontak Terdaftar')
                            ->relationship('groups', 'name')
                            ->multiple()
                            ->preload(),
                        Textarea::make('ai_summary')
                            ->label('Analisis Kebutuhan Leads (Oleh AI)')
                            ->columnSpanFull()
                            ->rows(3)
                            ->disabled()
                            ->placeholder('AI akan otomatis mengisi ringkasan profil & kebutuhan leads ini setelah menganalisis percakapan.'),
                    ])->columns(2),
            ])->columns(1);
    }
}
