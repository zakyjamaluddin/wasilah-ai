<?php

namespace App\Filament\Resources\BroadcastCampaigns\Schemas;

use App\Models\BroadcastTemplate;
use App\Models\Channel;
use App\Models\ContactGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BroadcastCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi Campaign Broadcast')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Campaign')
                            ->placeholder('Contoh: Broadcast Flash Sale Akhir Bulan')
                            ->required(),
                        Select::make('channel_id')
                            ->label('Pilih Akun WhatsApp Pengirim')
                            ->options(fn () => Channel::where('type', 'whatsapp')->where('status', 'connected')->pluck('name', 'id'))
                            ->required(),
                        Select::make('contact_group_id')
                            ->label('Pilih Grup Kontak Sasaran')
                            ->options(fn () => ContactGroup::pluck('name', 'id'))
                            ->required()
                            ->helperText('Pesan akan dikirimkan ke seluruh nomor di dalam grup kontak ini.'),
                        Select::make('broadcast_template_id')
                            ->label('Pilih Template (Opsional)')
                            ->options(fn () => BroadcastTemplate::pluck('name', 'id'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $template = BroadcastTemplate::find($state);
                                    if ($template) {
                                        $set('message', $template->message_template);
                                        $set('media_url', $template->media_url);
                                    }
                                }
                            }),
                        FileUpload::make('media_url')
                            ->label('Gambar Promosi (Opsional)')
                            ->image()
                            ->directory('broadcast-media')
                            ->disk('public'),
                        Textarea::make('message')
                            ->label('Isi Pesan Promosi')
                            ->rows(6)
                            ->required()
                            ->helperText('Gunakan {{name}} untuk menyapa nama customer.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columns(1);
    }
}
