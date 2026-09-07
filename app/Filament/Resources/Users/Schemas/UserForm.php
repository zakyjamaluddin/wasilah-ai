<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun Staff / Kepala Cabang')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->placeholder('Contoh: Ahmad Staff Cabang Surabaya')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Alamat Email Login')
                            ->email()
                            ->placeholder('ahmad@wasilah.com')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Password Akun')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Kosongkan jika tidak ingin mengubah password saat edit.'),

                         Select::make('roles')
                            ->label('Peran / Role Pengguna')
                            ->options(fn () => \Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->dehydrated(false)
                            ->required(),

                        // 🔥 2. PILIH KANTOR CABANG YANG DIBERI AKSES
                        Select::make('offices')
                            ->label('Kantor Cabang yang Boleh Diakses')
                            ->relationship('offices', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Staff ini HANYA akan bisa membuka dan melihat data di kantor cabang yang dicentang di sini.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columns(1);
    }
}
