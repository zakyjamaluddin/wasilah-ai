<?php

namespace App\Filament\Resources\Offices\Schemas;

use App\Models\ComposioAccount;
use Filament\Forms\Components\Select;
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
                        
                        // Tambahkan kode Select ini di dalam skema form OfficeResource:
                        Select::make('composio_account_id')
                            ->label('Akun Composio (FB & Instagram AI)')
                            ->placeholder('-- Pilih Akun Composio untuk Kantor Ini --')
                            ->relationship('composioAccount', 'name', function ($query) {
                                $query->where('is_active', true);
                            })
                            ->getOptionLabelFromRecordUsing(function (ComposioAccount $account) {
                                $terpakai = $account->offices()->count();
                                return "{$account->name} (Terpakai: {$terpakai}/{$account->max_offices} Slot)";
                            })
                            ->disableOptionWhen(function (string $value, $record) {
                                // Jika kantor ini sedang diedit dan akun ini memang miliknya, jangan di-disable
                                if ($record && $record->composio_account_id == $value) {
                                    return false;
                                }
                                // Jika slot sudah penuh (>= 4), opsi akan dinonaktifkan (abu-abu/tidak bisa dipilih)
                                $account = ComposioAccount::find($value);
                                return $account ? !$account->hasAvailableSlot() : true;
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('1 Akun Composio maksimal menangani 4 kantor.')
                            ->nullable(),
                    ])->columns(2),
            ])->columns(1);
    }
}
