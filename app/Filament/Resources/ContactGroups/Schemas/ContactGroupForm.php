<?php

namespace App\Filament\Resources\ContactGroups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContactGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
               TextInput::make('name')
                            ->label('Nama Grup Kontak')
                            ->placeholder('Contoh: Leads Seminar Jakarta / Pelanggan VIP')
                            ->required()
                            ->maxLength(255),
                Textarea::make('description')
                            ->label('Deskripsi / Catatan')
                            ->placeholder('Keterangan singkat tentang grup kontak ini')
                            ->rows(3),
            ]);
    }
}
