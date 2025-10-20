<?php

namespace App\Filament\Resources;

use App\Filament\Exports\SparepartExporter;
use App\Filament\Resources\SparepartResource\Pages;
use App\Filament\Resources\SparepartResource\RelationManagers;
use App\Models\Sparepart;
use Carbon\Carbon;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class SparepartResource extends Resource
{
    protected static ?string $model = Sparepart::class;

    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Detail')
                            ->schema([
                                Forms\Components\DatePicker::make('Tanggal')
                                    ->required()
                                    ->native(false)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $cycleTime = $get('Cycle_Time');
                                        if ($state && is_numeric($cycleTime)) {
                                            $tanggal = Carbon::parse($state);
                                            $tanggalKembali = $tanggal->addHours((int) $cycleTime);
                                            $set('Tanggal_Kembali', $tanggalKembali->format('Y-m-d'));
                                        }
                                    }),
                                Forms\Components\Select::make('Tipe_Maintenance')
                                    ->required()
                                    ->options([
                                        'PERBAIKAN' => 'PERBAIKAN',
                                        'PREVENTIVE' => 'PREVENTIVE',
                                        'IMPROVMENT' => 'IMPROVMENT',
                                    ])
                                    ->default('PERBAIKAN'),
                                Forms\Components\TextInput::make('Kelompok')
                                    ->required()
                                    ->maxLength(10),
                                Forms\Components\TextInput::make('Item')
                                    ->required()
                                    ->maxLength(30),
                                Forms\Components\RichEditor::make('Deskripsi')
                                    ->required()
                                    ->toolbarButtons([
                                        'blockquote',
                                        'bold',
                                        'bulletList',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'underline',
                                        'undo',
                                    ])
                                    ->columnSpan('full'),
                                Forms\Components\TextInput::make('Nama_Plant')
                                    ->required()
                                    ->maxLength(6),
                                Forms\Components\TextInput::make('Nama_Mesin')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('Nama_Bagian')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('Nama_Operator')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('Cycle_Time')
                                    ->helperText('Input dalam waktu Jam')
                                    ->numeric()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $tanggal = $get('Tanggal');
                                        if ($tanggal && is_numeric($state)) {
                                            $carbonDate = Carbon::parse($tanggal);
                                            $tanggalKembali = $carbonDate->addHours((int) $state);
                                            $set('Tanggal_Kembali', $tanggalKembali->format('Y-m-d'));
                                        }
                                    }),
                                Forms\Components\DatePicker::make('Tanggal_Kembali')
                                    ->native(false)
                                    ->helperText('Kalkulasi Otomatis')
                                    ->dehydrated(),
                            ])
                            ->collapsible()
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Penyimpanan')
                            ->schema([
                                Forms\Components\TextInput::make('Masuk')
                                    ->required()
                                    ->numeric()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        Self::calculateStoks($get, $set);
                                    }),
                                Forms\Components\TextInput::make('Keluar')
                                    ->required()
                                    ->numeric()
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        Self::calculateStoks($get, $set);
                                    }),
                                Forms\Components\TextInput::make('Stok')
                                    ->helperText('Kalkulasi Otomatis')
                                    ->dehydrated()
                                    ->required()
                                    ->numeric(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Tanggal')
                    ->date()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('Tipe_Maintenance')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('Kelompok')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Item')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Deskripsi')
                    ->html()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('total_in')
                //     ->state(fn(Sparepart $r) => $r->stockHistories()
                //         ->where('direction', 'IN')->sum('qty'))
                //     ->badge()->color('info'),
                // Tables\Columns\TextColumn::make('total_out')
                //     ->state(fn(Sparepart $r) => $r->stockHistories()
                //         ->where('direction', 'OUT')->sum('qty'))
                //     ->badge()->color('danger'),
                Tables\Columns\TextColumn::make('Masuk')
                    ->badge()
                    ->color('info')
                    ->numeric(),
                Tables\Columns\TextColumn::make('Keluar')
                    ->badge()
                    ->color('warning')
                    ->numeric(),
                // Tables\Columns\TextColumn::make('Stok')
                //     ->extraAttributes(function (Sparepart $record) {
                //         if ($record->Stok <= 5) {
                //             return ['class' => 'bg-danger-300 dark:bg-danger-600'];
                //         }

                //         return [];
                //     })
                //     ->numeric(),
                Tables\Columns\TextColumn::make('Stok')
                    ->tooltip('<= 5 Hampir Habis, <= 20 Stok Menipis')
                    ->badge()
                    ->numeric()
                    ->sortable()
                    ->color(function (Sparepart $record) {
                        if ($record->Stok === 0) {
                            return 'danger';
                        } elseif ($record->Stok <= 5) {
                            return 'danger';
                        } elseif ($record->Stok <= 20) {
                            return 'warning';
                        } else {
                            return 'success';
                        }
                    })
                    ->formatStateUsing(function (Sparepart $record) {
                        if ($record->Stok === 0) {
                            return 'Habis (0)';
                        } elseif ($record->Stok <= 5) {
                            return 'Hampir Habis (' . $record->Stok . ')';
                        } elseif ($record->Stok <= 20) {
                            return 'Stok Menipis (' . $record->Stok . ')';
                        } else {
                            return 'Stok Aman (' . $record->Stok . ')';
                        }
                    }),
                Tables\Columns\TextColumn::make('Nama_Plant')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Nama_Mesin')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Nama_Bagian')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Nama_Operator')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Cycle_Time')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(),
                Tables\Columns\TextColumn::make('Tanggal_Kembali')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                //
                DateRangeFilter::make('Tanggal')
                    ->label('Tanggal Pengecekan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Histori Stok')
                    ->slideOver()
                    ->icon('heroicon-m-eye'),
                Tables\Actions\Action::make('Mutasi Stok')
                    ->slideOver()
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        Forms\Components\Fieldset::make('Detail Mutasi Stok')
                            ->schema([
                                // ...
                                Forms\Components\Select::make('direction')
                                    ->required()
                                    ->live()
                                    ->options(['IN' => 'Tambah', 'OUT' => 'Kurangi', 'ADJUST' => 'Penyesuaian']),
                                Forms\Components\TextInput::make('qty')
                                    ->required()->numeric()->minValue(1)
                                    ->live()
                                    ->placeholder('0')
                                    ->label(fn(Get $get) => $get('direction') === 'ADJUST' ? 'Stok Akhir' : 'Qty')
                                    ->helperText(fn(Get $get) => $get('direction') === 'ADJUST'
                                        ? 'Masukkan stok akhir berdasarkan jumlah fisik'
                                        : 'Jumlah unit yang ditambah/kurangi'),
                                Forms\Components\Textarea::make('note')->maxLength(200)->columnSpan(2)->placeholder('Harap Di isi')->required(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (Sparepart $record, array $data) {
                        $actor = auth()->user()->name ?? null;

                        try {
                            match ($data['direction']) {
                                'IN'     => $record->increaseStock((int)$data['qty'], $data['note'] ?? null, $actor),
                                'OUT'    => $record->decreaseStock((int)$data['qty'], $data['note'] ?? null, $actor),
                                'ADJUST' => $record->adjustStock((int)$data['qty'], $data['note'] ?? null, $actor),
                            };

                            Notification::make()
                                ->title('Stok diperbarui')
                                ->success()
                                ->send();
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Gagal mutasi stok')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                // ->action(function (Sparepart $record, array $data) {
                //     $actor = auth()->user()->name ?? null;

                //     match ($data['direction']) {
                //         'IN'     => $record->increaseStock((int)$data['qty'], $data['note'] ?? null, $actor),
                //         'OUT'    => $record->decreaseStock((int)$data['qty'], $data['note'] ?? null, $actor),
                //         'ADJUST' => $record->adjustStock((int)$data['qty'], $data['note'] ?? null, $actor), // jika ADJUST kamu minta targetQty, sesuaikan
                //     };

                //     Notification::make()->title('Stok diperbarui')->success()->send();
                // }),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make('detailSparepart')
                        ->label('Detail Sparepart')
                        ->icon('heroicon-m-document-text')
                        ->slideOver()
                        ->infolist([
                            Components\Section::make('Informasi Utama')
                                ->schema([
                                    Components\TextEntry::make('id')->label('ID'),
                                    Components\TextEntry::make('Tanggal')->date('d M Y'),
                                    Components\TextEntry::make('Tipe_Maintenance'),
                                    Components\TextEntry::make('Kelompok'),
                                    Components\TextEntry::make('Item'),
                                    Components\TextEntry::make('Deskripsi'),
                                ])
                                ->columns(2),

                            Components\Section::make('Stok')
                                ->schema([
                                    Components\TextEntry::make('Masuk')->numeric(),
                                    Components\TextEntry::make('Keluar')->numeric(),
                                    Components\TextEntry::make('Stok')
                                        ->badge()
                                        ->color(fn($state) => $state <= 5 ? 'danger' : ($state <= 20 ? 'warning' : 'success'))
                                        ->formatStateUsing(fn($state) => $state <= 5
                                            ? "Hampir Habis ($state)"
                                            : ($state <= 20 ? "Stok Menipis ($state)" : "Stok Aman ($state)")),
                                ])
                                ->columns(3),

                            Components\Section::make('Lokasi & Operator')
                                ->schema([
                                    Components\TextEntry::make('Nama_Plant')->label('Plant'),
                                    Components\TextEntry::make('Nama_Mesin')->label('Mesin'),
                                    Components\TextEntry::make('Nama_Bagian')->label('Bagian'),
                                    Components\TextEntry::make('Nama_Operator')->label('Operator')->placeholder('-'),
                                ])
                                ->columns(4),

                            Components\Section::make('Waktu & Cycle')
                                ->schema([
                                    Components\TextEntry::make('Cycle_Time')->label('Cycle (jam)')->numeric(),
                                    Components\TextEntry::make('Tanggal_Kembali')->date('d M Y')->placeholder('-'),
                                    Components\TextEntry::make('created_at')->dateTime('d M Y H:i')->label('Dibuat'),
                                    Components\TextEntry::make('updated_at')->dateTime('d M Y H:i')->label('Diubah'),
                                ])
                                ->columns(4),
                        ]),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Data')
                        ->slideOver()
                        ->form([
                            Forms\Components\Section::make('Detail')
                                ->schema([
                                    Forms\Components\DatePicker::make('Tanggal')
                                        ->required()
                                        ->native(false)
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $cycleTime = $get('Cycle_Time');
                                            if ($state && is_numeric($cycleTime)) {
                                                $tanggal = Carbon::parse($state);
                                                $tanggalKembali = $tanggal->addHours((int) $cycleTime);
                                                $set('Tanggal_Kembali', $tanggalKembali->format('Y-m-d'));
                                            }
                                        }),
                                    Forms\Components\Select::make('Tipe_Maintenance')
                                        ->required()
                                        ->options([
                                            'PERBAIKAN' => 'PERBAIKAN',
                                            'PREVENTIVE' => 'PREVENTIVE',
                                            'IMPROVMENT' => 'IMPROVMENT',
                                        ])
                                        ->default('PERBAIKAN'),
                                    Forms\Components\TextInput::make('Kelompok')
                                        ->required()
                                        ->maxLength(10),
                                    Forms\Components\TextInput::make('Item')
                                        ->required()
                                        ->maxLength(30),
                                    Forms\Components\RichEditor::make('Deskripsi')
                                        ->required()
                                        ->toolbarButtons([
                                            'blockquote',
                                            'bold',
                                            'bulletList',
                                            'italic',
                                            'orderedList',
                                            'redo',
                                            'underline',
                                            'undo',
                                        ])
                                        ->columnSpan('full'),
                                    Forms\Components\TextInput::make('Nama_Plant')
                                        ->required()
                                        ->maxLength(6),
                                    Forms\Components\TextInput::make('Nama_Mesin')
                                        ->required()
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('Nama_Bagian')
                                        ->required()
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('Nama_Operator')
                                        ->maxLength(20),
                                    Forms\Components\TextInput::make('Cycle_Time')
                                        ->helperText('Input dalam waktu Jam')
                                        ->numeric()
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $tanggal = $get('Tanggal');
                                            if ($tanggal && is_numeric($state)) {
                                                $carbonDate = Carbon::parse($tanggal);
                                                $tanggalKembali = $carbonDate->addHours((int) $state);
                                                $set('Tanggal_Kembali', $tanggalKembali->format('Y-m-d'));
                                            }
                                        }),
                                    Forms\Components\DatePicker::make('Tanggal_Kembali')
                                        ->native(false)
                                        ->helperText('Kalkulasi Otomatis')
                                        ->dehydrated(),
                                ])
                                ->collapsible()
                                ->columns(2),
                        ]),
                    // Tables\Actions\Action::make('Update Stok')
                    //     ->icon('heroicon-m-pencil-square')
                    //     ->fillForm(fn(Sparepart $record): array => [
                    //         'Masuk' => $record->Masuk,
                    //         'Keluar' => $record->Keluar,
                    //         'Stok' => $record->Stok
                    //     ])
                    //     ->form([
                    //         Forms\Components\Section::make('Penyimpanan')
                    //             ->schema([
                    //                 Forms\Components\Grid::make(3)
                    //                     ->schema([
                    //                         Forms\Components\TextInput::make('Masuk')
                    //                             ->required()
                    //                             ->numeric()
                    //                             ->live(debounce: 500)
                    //                             ->afterStateUpdated(function (Get $get, Set $set) {
                    //                                 Self::calculateStoks($get, $set);
                    //                             }),
                    //                         // ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    //                         //     $keluar = (int) $get('Keluar');
                    //                         //     $masuk = (int) $state;

                    //                         //     $set('Stok', $masuk - $keluar);
                    //                         // }),
                    //                         Forms\Components\TextInput::make('Keluar')
                    //                             ->required()
                    //                             ->numeric()
                    //                             ->live(debounce: 500)
                    //                             ->afterStateUpdated(function (Get $get, Set $set) {
                    //                                 Self::calculateStoks($get, $set);
                    //                             }),
                    //                         // ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    //                         //     $masuk = (int) $get('Masuk');
                    //                         //     $keluar = (int) $state;

                    //                         //     $set('Stok', $masuk - $keluar);
                    //                         // }),
                    //                         Forms\Components\TextInput::make('Stok')
                    //                             ->helperText('Kalkulasi Otomatis')
                    //                             ->required()
                    //                             ->dehydrated()
                    //                             ->numeric(),
                    //                     ]),
                    //             ]),
                    //     ])
                    //     ->action(function (Sparepart $spare, array $data): void {
                    //         $spare->masuk = $data['Masuk'];
                    //         $spare->keluar = $data['Keluar'];
                    //         $spare->stok = $data['Stok'];
                    //         $spare->save();

                    //         Notification::make()
                    //             ->title('Data Updated')
                    //             ->success()
                    //             ->send();
                    //     }),
                ]),

            ])
            ->headerActions([
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()
                    //     ->action(function () {
                    //         Notification::make()->title('Fitur ini dinonaktifkan')->warning()->send();
                    //     }),
                    Tables\Actions\DeleteBulkAction::make()
                ]),
                ExportBulkAction::make()
                    ->exporter(SparepartExporter::class)
                    ->label('Ekspor Data')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('primary')
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->formats([
                        ExportFormat::Xlsx,
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\RepeatableEntry::make('stockHistories')
                    ->label('Histori Stok')
                    ->schema([
                        Components\TextEntry::make('updated_at')->dateTime('d M Y H:i')->label('Waktu'),
                        Components\TextEntry::make('direction')
                            ->label('Posisi')
                            ->badge()
                            ->colors([
                                'success' => fn($state) => $state === 'IN',
                                'danger' => fn($state) => $state === 'OUT',
                                'gray'    => fn($state) => $state === 'ADJUST',
                            ])
                            ->formatStateUsing(fn($state) => match ($state) {
                                'IN' => 'Masuk',
                                'OUT' => 'Keluar',
                                'ADJUST' => 'Penyesuaian',
                                default => $state,
                            }),
                        Components\TextEntry::make('qty')->numeric()->label('Qty'),
                        Components\TextEntry::make('balance_after')->numeric()->label('Stok Akhir'),
                        Components\TextEntry::make('note')->label('Catatan')->placeholder('-'),
                        Components\TextEntry::make('actor_name')->label('Operator')->placeholder('-'),
                    ])
                    ->columns(6)
                    ->columnSpanFull()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            // RelationManagers\StockHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareparts::route('/'),
            // 'create' => Pages\CreateSparepart::route('/create'),
            // 'view' => Pages\ViewSparepart::route('/{record}'),
            // 'edit' => Pages\EditSparepart::route('/{record}/edit'),
        ];
    }

    public static function calculateStoks(Get $get, Set $set): void
    {
        $keluar = (int) $get('Keluar');
        $set('Stok', $get('Masuk') - $keluar);
    }
}
