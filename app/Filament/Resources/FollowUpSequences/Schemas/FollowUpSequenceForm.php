<?php

namespace App\Filament\Resources\FollowUpSequences\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class FollowUpSequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Sequence Follow-Up')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Sequence Kampanye')
                            ->placeholder('Contoh: Nurturing Jamaah Umroh 2026')
                            ->required(),
                        Select::make('trigger_event')
                            ->label('Pemicu Masuk Sequence')
                            ->options([
                                'on_new_lead' => 'Otomatis: Saat Ada Chat Masuk Baru',
                                'on_pipeline_change' => 'Otomatis: Saat Status Prospek Berubah',
                                'manual_only' => 'Manual: Hanya Didaftarkan oleh CS di Chat',
                            ])
                            ->default('on_new_lead')
                            ->reactive()
                            ->required(),
                        Select::make('pipeline_trigger_stage')
                            ->label('Pilih Status Prospek Pemicu')
                            ->options([
                                'lead' => 'Baru Masuk (Lead)',
                                'cold_prospect' => 'Cold Prospect',
                                'warm_prospect' => 'Warm Prospect',
                                'hot_prospect' => 'Hot Prospect 🔥',
                                'closing' => 'Tahap Closing',
                            ])
                            ->visible(fn (Get $get) => $get('trigger_event') === 'on_pipeline_change')
                            ->required(fn (Get $get) => $get('trigger_event') === 'on_pipeline_change'),
                        Toggle::make('stop_on_reply')
                            ->label('Otomatis Jeda jika Customer Membalas')
                            ->helperText('Sequence berhenti sementara agar CS manusia bisa langsung closing.')
                            ->default(true),
                        Toggle::make('stop_on_closing')
                            ->label('Berhenti Total jika Status Menjadi Won/Closing 🎉')
                            ->default(true),
                        Toggle::make('only_work_hours')
                            ->label('Hanya Kirim di Jam Kerja Wajar (08:00 - 20:00)')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Status Sequence Aktif')
                            ->default(true),
                    ])->columns(2),

                // 2. 🔥 REPEATER LANGKAH-LANGKAH (STEP 1, STEP 2 ... STEP N TANPA BATAS)
                Section::make('Rangkaian Langkah Follow-Up (Drip Steps)')
                    ->description('Tambahkan langkah follow-up sebanyak yang Anda butuhkan (Step 1, Step 2, dst).')
                    ->schema([
                        Repeater::make('steps')
                            ->relationship('steps')
                            ->orderColumn('step_order')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('delay_value')
                                            ->label('Jeda Waktu')
                                            ->numeric()
                                            ->default(1)
                                            ->required(),
                                        Select::make('delay_unit')
                                            ->label('Satuan Waktu')
                                            ->options([
                                                'minutes' => 'Menit',
                                                'hours' => 'Jam',
                                                'days' => 'Hari',
                                                'weeks' => 'Minggu',
                                            ])
                                            ->default('days')
                                            ->required(),
                                        Select::make('content_type')
                                            ->label('Tipe Pesan')
                                            ->options([
                                                'template' => 'Teks Template Manual',
                                                'ai_prompt' => '🧠 AI Gemini Smart Message',
                                            ])
                                            ->default('template')
                                            ->reactive()
                                            ->required(),
                                    ]),
                                Textarea::make('message_template')
                                    ->label('Teks Pesan Template')
                                    ->placeholder("Halo {{name}}! Ada update jadwal keberangkatan umroh...")
                                    ->visible(fn (Get $get) => $get('content_type') === 'template')
                                    ->rows(3),
                                Textarea::make('ai_prompt')
                                    ->label('Instruksi Gaya Bicara AI Gemini')
                                    ->placeholder("Sapa {{name}}, tanyakan apakah ada kendala dengan tanggal keberangkatan, dan sebutkan sisa seat.")
                                    ->visible(fn (Get $get) => $get('content_type') === 'ai_prompt')
                                    ->rows(3),
                                FileUpload::make('media_url')
                                    ->label('Lampiran (PDF Brosur, Video Hotel, Foto)')
                                    ->directory('followup-media')
                                    ->disk('public'),
                            ])
                            ->defaultItems(2)
                            ->addActionLabel('+ Tambah Langkah Follow-Up Baru')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 'Langkah ' . ($state['step_order'] ?? '') . ': Jeda ' . ($state['delay_value'] ?? '1') . ' ' . ($state['delay_unit'] ?? 'days')),
                    ]),
            ]);
    }
}
