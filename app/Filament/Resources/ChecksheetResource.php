<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecksheetResource\Pages;
use App\Models\Checksheet;
use App\Models\Mesin;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Js;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ChecksheetResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Checksheet::class;

    protected static ?string $pluralLabel = 'PM';

    protected static ?string $navigationIcon = 'heroicon-s-clipboard-document-check';

    private const CHECK_OPTIONS = [
        'good' => 'Good',
        'not good' => 'Not Good',
    ];

    private const PROCESS_OPTIONS = [
        'Drawing' => 'Drawing',
        'Stranding' => 'Stranding',
        'Cabling' => 'Cabling',
        'Extruder' => 'Extruder',
        'Bunching' => 'Bunching',
        'Tapping' => 'Tapping',
        'Tinning' => 'Tinning',
        'Coloring' => 'Coloring',
    ];

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'publish',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                self::operatorSection(),
                self::machineSection(),

                Section::make('ELECTRICAL CHECKLIST')
                    ->description('Klik untuk membuka atau menutup pemeriksaan electrical.')
                    // ->icon('heroicon-o-bolt')
                    ->schema(self::electricalChecklistSchema())
                    ->collapsible()
                    ->collapsed(),

                Section::make('MECHANICAL CHECKLIST')
                    ->description('Klik untuk membuka atau menutup pemeriksaan mechanical.')
                    // ->icon('heroicon-o-wrench-screwdriver')
                    ->schema(self::mechanicalChecklistSchema())
                    ->collapsible()
                    ->collapsed(),

                self::imageSection(),
            ]);
    }

    private static function operatorSection(): Section
    {
        return Section::make()
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('plant_area')
                            ->label('Nama Plant')
                            ->native(false)
                            ->required()
                            ->options(fn(): array => self::plantOptions())
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('nama_mesin', null)),

                        TextInput::make('nama_operator')
                            ->label('Nama')
                            ->required()
                            ->datalist(fn(): array => self::checksheetDatalist('nama_operator'))
                            ->afterStateUpdated(function ($livewire, TextInput $component): void {
                                $livewire->validateOnly($component->getStatePath());
                            }),

                        TextInput::make('posisi_operator')
                            ->label('Posisi')
                            ->maxLength(10)
                            ->required()
                            ->live()
                            ->datalist(fn(): array => self::checksheetDatalist('posisi_operator'))
                            ->afterStateUpdated(function ($livewire, TextInput $component): void {
                                $livewire->validateOnly($component->getStatePath());
                            }),
                    ]),
            ]);
    }

    private static function machineSection(): Section
    {
        return Section::make()
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('tipe_proses')
                            ->label('Jenis Mesin')
                            ->options(self::PROCESS_OPTIONS)
                            ->default('Drawing')
                            ->native(false)
                            ->required()
                            ->live()
                            ->disabledOn('edit'),

                        Select::make('nama_mesin')
                            ->label('Nama Mesin')
                            ->required()
                            ->disabled(fn(Get $get): bool => blank($get('plant_area')))
                            ->helperText(
                                fn(Get $get): ?string => blank($get('plant_area'))
                                    ? 'Pilih plant terlebih dahulu.'
                                    : null
                            )
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get): array {
                                $plant = $get('plant_area');

                                if (blank($plant)) {
                                    return [];
                                }

                                return Mesin::query()
                                    ->where('nama_plant', $plant)
                                    ->orderBy('nama_mesin')
                                    ->pluck('nama_mesin', 'nama_mesin')
                                    ->all();
                            }),

                        TextInput::make('hours_meter')
                            ->label('Hours Meter')
                            ->numeric()
                            ->required(),

                        DatePicker::make('date')
                            ->label('Tanggal Cek')
                            ->timezone('Asia/Jakarta')
                            ->native(false)
                            ->required(),

                        TimePicker::make('time_start')
                            ->label('Time Start')
                            ->timezone('Asia/Jakarta')
                            ->seconds(false)
                            ->suffix('WIB')
                            ->required(),

                        TimePicker::make('time_end')
                            ->label('Time End')
                            ->timezone('Asia/Jakarta')
                            ->seconds(false)
                            ->suffix('WIB')
                            ->required(),
                    ]),
            ]);
    }

    private static function electricalChecklistSchema(): array
    {
        return [
            self::checklistFieldset(
                'MOTOR PENGGERAK',
                [
                    ['elektrik_carbon_brush', 'Periksa Carbon Brush'],
                    ['elektrik_suara', 'Periksa Suara'],
                    ['elektrik_getaran', 'Periksa Getaran'],
                    ['elektrik_suhu', 'Periksa Suhu'],
                    ['elektrik_tahanan_isolasi', 'Periksa Tahanan Isolasi'],
                    ['elektrik_ampere_motor', 'Periksa Ampere Motor'],
                ],
                'remarks_elektrik_motor_penggerak',
            ),

            self::checklistFieldset(
                'HEATER',
                [
                    ['elektrik_suhu_heater', 'Periksa Suhu'],
                    ['elektrik_fungsi_pemanas', 'Periksa Fungsi Pemanas'],
                ],
                'remarks_elektrik_heater',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Extruder'),

            self::checklistFieldset(
                'SISTEM KONTROL',
                [
                    ['elektrik_tombol', 'Periksa Tombol'],
                    ['elektrik_layar', 'Periksa Layar'],
                    ['elektrik_PLC', 'Periksa PLC'],
                    ['elektrik_kontaktor', 'Periksa Kontaktor'],
                    ['elektrik_drive-inverter', 'Periksa Tahanan Drive/Inverter'],
                ],
                'remarks_elektrik_sistem_control',
            ),

            self::checklistFieldset(
                'KABEL DAN KONEKTOR',
                [
                    ['elektrik_kondisi_kabel', 'Periksa Kondisi Kabel'],
                    ['elektrik_socket_kabel', 'Periksa Socket Kabel'],
                ],
                'remarks_elektrik_kabel_dan_konektor',
            ),

            self::checklistFieldset(
                'SISTEM HIDROLIK',
                [
                    ['elektrik_kebocoran', 'Periksa Kebocoran'],
                    ['elektrik_tekanan', 'Periksa Tekanan'],
                ],
                'remarks_elektrik_sistem_hidrolik',
            ),

            self::checklistFieldset(
                'SISTEM PEMANAS',
                [
                    ['elektrik_sispemanas_suhu', 'Periksa Suhu'],
                    ['elektrik_kestabilan_pemanas', 'Periksa Kestabilan Pemanas'],
                ],
                'remarks_elektrik_sistem_pemanas',
            )->visible(
                fn(Get $get): bool => in_array($get('tipe_proses'), ['Tinning', 'Coloring'], true)
            ),

            self::checklistFieldset(
                'SISTEM PENDINGIN TINNING',
                [
                    ['elektrik_kebersihan_kipas', 'Periksa Kebersihan Kipas'],
                    ['elektrik_fungsi_kipas', 'Periksa Fungsi Kipas'],
                ],
                'remarks_elektrik_sistem_pendingin_tinning',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Tinning'),

            self::checklistFieldset(
                'POMPA PEWARNA',
                [
                    ['elektrik_kebersihan', 'Periksa Kebersihan'],
                    ['elektrik_aliran_cairan', 'Periksa Aliran Cairan'],
                    ['elektrik_tekanan_coloring', 'Periksa Tekanan'],
                ],
                'remarks_elektrik_sistem_pewarna',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Coloring'),

            self::checklistFieldset(
                'SISTEM PENDINGIN MOTOR',
                [
                    ['elektrik_filter', 'Periksa Filter'],
                    ['elektrik_blower', 'Periksa Blower'],
                    ['elektrik_sirkulasi', 'Periksa Sirkulasi'],
                ],
                'remarks_elektrik_sistem_pendingin_motor',
            ),

            self::checklistFieldset(
                'THERMOCOUPLE',
                [
                    ['elektrik_kalibrasi_suhu', 'Periksa Kalibrasi Suhu'],
                ],
                'remarks_elektrik_thermocouple',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Extruder'),
        ];
    }

    private static function mechanicalChecklistSchema(): array
    {
        return [
            self::checklistFieldset(
                'GEARBOX',
                [
                    ['mekanik_gearbox_pelumasan', 'Periksa Pelumasan (Check Level)'],
                    ['mekanik_gearbox_kebersihan', 'Periksa Kebersihan'],
                    ['mekanik_gearbox_suara', 'Periksa Suara'],
                ],
                'remarks_mekanik_gearbox',
            ),

            self::checklistFieldset(
                'SISTEM PENDINGIN',
                [
                    ['mekanik_sispendingin_aliran_pendingin', 'Periksa Aliran Pendingin'],
                    ['mekanik_sispendingin_kebersihan_pipa', 'Periksa Kebersihan Pipa'],
                    ['mekanik_sispendingin_sirkulasi_pipa', 'Periksa Sirkulasi Pipa'],
                ],
                'remarks_mekanik_sispendingin',
            )->visible(
                fn(Get $get): bool => in_array($get('tipe_proses'), ['Drawing', 'Extruder'], true)
            ),

            self::checklistFieldset(
                'SHAFT',
                [
                    ['mekanik_shaft_keausan', 'Periksa Keausan'],
                    ['mekanik_shaft_kerusakan', 'Periksa Kerusakan'],
                ],
                'remarks_mekanik_shaft',
            ),

            self::checklistFieldset(
                'ANEALING',
                [
                    ['mekanik_anealing_anealing', 'Periksa Anealing'],
                    ['mekanik_anealing_carbon_brush', 'Periksa Carbon Brush'],
                ],
                'remarks_mekanik_anealing',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Drawing'),

            self::checklistFieldset(
                'ROLL CAPSTAN',
                [
                    ['mekanik_rollcap_keausan', 'Periksa Keausan'],
                    ['mekanik_rollcap_kerusakan', 'Periksa Kerusakan'],
                ],
                'remarks_mekanik_rollcap',
            )->hidden(
                fn(Get $get): bool => in_array($get('tipe_proses'), ['Extruder', 'Coloring'], true)
            ),

            self::checklistFieldset(
                'PULLEY DAN BELT',
                [
                    ['mekanik_pulleybelt_kekencangan', 'Periksa Kekencangan Belt'],
                    ['mekanik_pulleybelt_ketebalan', 'Periksa Ketebalan Belt'],
                ],
                'remarks_mekanik_pulleybelt',
            )->hidden(fn(Get $get): bool => $get('tipe_proses') === 'Coloring'),

            self::checklistFieldset(
                'BEARING',
                [
                    ['mekanik_bearing_pelumasan', 'Periksa Pelumasan'],
                    ['mekanik_bearing_kondisi_fisik', 'Periksa Kondisi Fisik'],
                ],
                'remarks_mekanik_bearing',
            ),

            self::checklistFieldset(
                'ALIGNMENT MESIN',
                [
                    ['mekanik_alignmesin_kesejajaran', 'Periksa Kesejajaran Shaft dan Motor'],
                ],
                'remarks_mekanik_alignmesin',
            ),

            self::checklistFieldset(
                'GEAR RANTAI',
                [
                    ['mekanik_gearrantai_pelumasan', 'Periksa Pelumasan'],
                    ['mekanik_gearrantai_keausan', 'Periksa Keausan'],
                ],
                'remarks_mekanik_gearrantai',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Stranding'),

            self::checklistFieldset(
                'SCREW DAN BARREL',
                [
                    ['mekanik_screewbarel_kondisi', 'Periksa Kondisi Keausan'],
                    ['mekanik_screewbarel_kerusakan', 'Periksa Kerusakan'],
                ],
                'remarks_mekanik_screewbarel',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Extruder'),

            self::checklistFieldset(
                'MESIN TINNING',
                [
                    ['mekanik_mesintinning_kebersihan', 'Periksa Kondisi Kebersihan'],
                    ['mekanik_mesintinning_roller', 'Periksa Kondisi Roller'],
                ],
                'remarks_mekanik_mesintinning',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Tinning'),

            self::checklistFieldset(
                'SISTEM PENDINGIN COLORING',
                [
                    ['mekanik_sispencoloring_aliran', 'Periksa Aliran'],
                    ['mekanik_sispencoloring_kebersihan_pipa', 'Periksa Kebersihan Pipa'],
                    ['mekanik_sispencoloring_flowmeter_n2', 'Periksa Flowmeter N2'],
                ],
                'remarks_mekanik_sispencoloring',
            )->visible(fn(Get $get): bool => $get('tipe_proses') === 'Coloring'),
        ];
    }

    private static function checklistFieldset(
        string $title,
        array $checks,
        string $remarksField,
    ): Fieldset {
        $fields = array_map(
            fn(array $check): Radio => self::checkRadio($check[0], $check[1]),
            $checks,
        );

        $fields[] = TextInput::make($remarksField)
            ->label('Catatan');

        return Fieldset::make($title)
            ->schema($fields)
            ->columns(1);
    }

    private static function checkRadio(string $name, string $label): Radio
    {
        return Radio::make($name)
            ->label($label)
            ->options(self::CHECK_OPTIONS)
            ->inline()
            ->required();
    }

    private static function imageSection(): Section
    {
        return Section::make('Dokumentasi')
            // ->description('Upload gambar sebelum dan sesudah pekerjaan.')
            ->schema([
                Grid::make(2)
                    ->schema([
                        self::imageUpload('image_before', 'Before'),
                        self::imageUpload('image_after', 'After'),
                    ]),
            ]);
    }

    private static function imageUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->imageEditor()
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ]);
    }

    private static function plantOptions(): array
    {
        return Mesin::query()
            ->whereNotNull('nama_plant')
            ->distinct()
            ->orderBy('nama_plant')
            ->pluck('nama_plant', 'nama_plant')
            ->all();
    }

    private static function checksheetDatalist(string $column): array
    {
        return DB::table('checksheets')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Pengecekan')
                    ->date()
                    ->sortable()
                    ->extraHeaderAttributes(['class' => 'w-8']),

                Tables\Columns\TextColumn::make('nama_operator')
                    ->label('Nama Operator'),

                Tables\Columns\TextColumn::make('time_start')
                    ->label('Mulai'),

                Tables\Columns\TextColumn::make('time_end')
                    ->label('Selesai'),

                Tables\Columns\TextColumn::make('plant_area')
                    ->label('Plant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipe_proses')
                    ->label('Jenis Mesin')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama_mesin')
                    ->label('Nama Mesin')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                DateRangeFilter::make('date')
                    ->label('Tanggal Pengecekan'),

                SelectFilter::make('plant_area')
                    ->label('Plant')
                    ->options(fn(): array => self::plantOptions()),
            ])
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
            )
            ->actions([
                Tables\Actions\EditAction::make()
                    ->closeModalByEscaping(false)
                    ->closeModalByClickingAway(false)
                    ->slideOver(),

                Tables\Actions\ViewAction::make()
                    ->closeModalByEscaping(false)
                    ->closeModalByClickingAway(false)
                    ->slideOver(),

                Tables\Actions\Action::make('publish')
                    ->label('Word')
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-on-square')
                    ->url(fn(Checksheet $record): string => route('word', $record))
                    ->visible(
                        fn(): bool => auth()->user()?->can('publish_checksheet') ?? false
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Word')
                        ->icon('heroicon-m-arrow-down-on-square')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publish PM terpilih')
                        ->modalDescription(
                            fn(Collection $records): string =>
                            $records->count() . ' PM akan diunduh dalam satu file ZIP.'
                        )
                        ->action(function (Collection $records, $livewire): void {
                            $downloadUrl = route('word.bulk', [
                                'orders' => $records->modelKeys(),
                            ]);

                            $livewire->js(
                                'window.location.assign(' . Js::from($downloadUrl) . ')'
                            );
                        })
                        ->visible(
                            fn(): bool => auth()->user()?->can('publish_checksheet') ?? false
                        )
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecksheets::route('/'),
        ];
    }
}
