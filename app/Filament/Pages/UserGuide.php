<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class UserGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;
    protected static ?string $navigationLabel = 'Buku Panduan';
    protected static ?string $title = 'Panduan & Dokumentasi Wasilah AI';
    protected static ?string $slug = 'panduan-penggunaan';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.user-guide';

    /**
     * Halaman panduan ini selalu bisa dibuka oleh semua pengguna di kantor mana pun.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
