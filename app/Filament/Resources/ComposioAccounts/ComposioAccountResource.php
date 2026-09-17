<?php

namespace App\Filament\Resources\ComposioAccounts;

use App\Filament\Resources\ComposioAccounts\Pages\CreateComposioAccount;
use App\Filament\Resources\ComposioAccounts\Pages\EditComposioAccount;
use App\Filament\Resources\ComposioAccounts\Pages\ListComposioAccounts;
use App\Filament\Resources\ComposioAccounts\Schemas\ComposioAccountForm;
use App\Filament\Resources\ComposioAccounts\Tables\ComposioAccountsTable;
use App\Models\ComposioAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ComposioAccountResource extends Resource
{
    protected static ?string $model = ComposioAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // 🔥 KUNCI PERBAIKAN 1: Matikan scope tenant agar tidak mencari relasi office()
    protected static bool $isScopedToTenant = false;

    // 🔥 KUNCI PERBAIKAN 2: Hanya Super Admin yang boleh melihat menu ini di sidebar
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->id === 1 || $user->hasRole('super_admin'));
    }

    public static function form(Schema $schema): Schema
    {
        return ComposioAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComposioAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComposioAccounts::route('/'),
            'create' => CreateComposioAccount::route('/create'),
            'edit' => EditComposioAccount::route('/{record}/edit'),
        ];
    }
}
