<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope'),

                // Kolom Role Pengguna


                // 🔥 KOLOM ROLE PRESISI LANGSUNG DARI DATABASE
                TextColumn::make('custom_role')
                    ->label('Peran (Role)')
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'info' => 'kepala_cabang',
                        'success' => 'staff_cs',
                    ])
                    ->getStateUsing(function (User $record) {
                        $roleName = \Illuminate\Support\Facades\DB::table('model_has_roles')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('model_has_roles.model_id', $record->id)
                            ->value('roles.name');

                        return $roleName ?: 'Belum Ada Role';
                    }),

                // Kolom Cabang yang Diberi Akses
                TagsColumn::make('offices.name')
                    ->label('Akses Kantor Cabang'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
