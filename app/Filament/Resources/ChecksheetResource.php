<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecksheetResource\Pages;
// use App\Filament\Resources\ChecksheetResource\RelationManagers;
use App\Models\Checksheet;
use App\Models\Mesin;
use Filament\Forms\Get;
use Filament\Forms\Set;
// use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Filament\Forms\Get;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Blade;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ChecksheetResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Checksheet::class;

    //change page label name
    protected static ?string $pluralLabel = "PM";

    protected static ?string $navigationIcon = 'heroicon-s-clipboard-document-check';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'publish'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('plant_area')
                                    ->label('Nama Plant')
                                    ->native(false)
                                    ->required()
                                    ->options(function () {
                                        return Mesin::query()
                                            ->distinct()
                                            ->orderBy('nama_plant')
                                            ->pluck('nama_plant', 'nama_plant');
                                    })
                                    // ->options([
                                    //     'PLANT A' => 'PLANT A',
                                    //     'PLANT B' => 'PLANT B',
                                    //     'PLANT C' => 'PLANT C',
                                    //     'PLANT D' => 'PLANT D',
                                    //     'PLANT E' => 'PLANT E',
                                    //     'PLANT SS' => 'PLANT SS',
                                    // ])
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('nama_mesin', null);
                                    }),
                                Forms\Components\TextInput::make('nama_operator')
                                    ->required()
                                    ->label('Nama')
                                    ->datalist(
                                        DB::table('checksheets')
                                            ->distinct()
                                            ->pluck('nama_operator')
                                            ->toArray()
                                    )
                                    ->afterStateUpdated(function ($livewire, Forms\Components\TextInput $component) {
                                        $livewire->validateOnly($component->getStatePath());
                                    }),
                                Forms\Components\TextInput::make('posisi_operator')
                                    ->maxLength(10)
                                    ->live()
                                    ->required()
                                    ->label('Posisi')
                                    ->datalist(
                                        DB::table('checksheets')
                                            ->distinct()
                                            ->pluck('posisi_operator')
                                            ->toArray()
                                    )
                                    ->afterStateUpdated(function ($livewire, Forms\Components\TextInput $component) {
                                        $livewire->validateOnly($component->getStatePath());
                                    }),
                            ])
                    ]),
                Forms\Components\Section::make()
                    ->label("")
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('tipe_proses')
                                    ->label('Jenis Mesin')
                                    ->required()
                                    ->options([
                                        'Drawing' => 'Drawing',
                                        'Stranding' => 'Stranding',
                                        'Cabling' => 'Cabling',
                                        'Extruder' => 'Extruder',
                                        'Bunching' => 'Bunching',
                                        'Tapping' => 'Tapping',
                                        'Tinning' => 'Tinning',
                                        'Coloring' => 'Coloring',
                                    ])
                                    ->default('Drawing')
                                    ->prefix('CheckSheet')
                                    ->native(false)
                                    ->reactive(),
                                // Forms\Components\TextInput::make('nama_mesin')
                                //     ->label("Nama mesin")
                                //     ->required()
                                //     ->datalist(
                                //         DB::table('checksheets')
                                //             ->distinct()
                                //             ->pluck('nama_mesin')
                                //             ->toArray()
                                //     ),
                                Forms\Components\Select::make('nama_mesin')
                                    ->label("Nama Mesin")
                                    ->required()
                                    ->disabled(fn(Get $get) => blank($get('plant_area')))
                                    ->helperText(fn(Get $get) => blank($get('plant_area')) ? 'Select the plant first' : null)
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Get $get) {
                                        $plant = $get('plant_area');

                                        if (! $plant) {
                                            return [];
                                        }

                                        return Mesin::where('nama_plant', $plant)
                                            ->orderBy('nama_mesin', 'asc')
                                            ->pluck('nama_mesin', 'nama_mesin'); //simpan nama(string)
                                    }),
                                Forms\Components\TextInput::make('hours_meter')
                                    ->label("Hours Meter")
                                    ->numeric()
                                    ->required(),
                                Forms\Components\DatePicker::make('date')
                                    ->timezone('Asia/Jakarta')
                                    ->label('Tanggal Cek')
                                    ->native(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('time_start')
                                    ->timezone('Asia/Jakarta')
                                    ->label('Time Start')
                                    ->seconds(false)
                                    ->suffix('WIB')
                                    ->required(),
                                Forms\Components\TimePicker::make('time_end')
                                    ->timezone('Asia/Jakarta')
                                    ->label('Time End')
                                    ->suffix('WIB')
                                    ->seconds(false)
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('ELECTRICAL CHECKLIST')
                            ->schema([
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // ELECTRICAL CHECKLIST
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // MOTOR PENGGERAK
                                Forms\Components\Fieldset::make('MOTOR PENGGERAK')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_carbon_brush')
                                            ->label('Periksa Carbon Brush')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_suara')
                                            ->label('Periksa Suara')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_getaran')
                                            ->label('Periksa Getaran')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_suhu')
                                            ->label('Periksa Suhu')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_tahanan_isolasi')
                                            ->label('Periksa Tahanan Isolasi')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_ampere_motor')
                                            ->label('Periksa Ampere Motor')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_motor_penggerak'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // HEATER
                                Forms\Components\Fieldset::make('HEATER')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_suhu_heater')
                                            ->label('Periksa Suhu')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_fungsi_pemanas')
                                            ->label('Periksa Fungsi Pemanas')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_heater'),
                                    ])
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Extruder')
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM KONTROL
                                Forms\Components\Fieldset::make('SISTEM KONTROL')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_tombol')
                                            ->label('Periksa Tombol')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_layar')
                                            ->label('Periksa Layar')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_PLC')
                                            ->label('Periksa PLC')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_kontaktor')
                                            ->label('Periksa Kontaktor')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_drive-inverter')
                                            ->label('Periksa Tahanan Drive/Inverter')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_control'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // KABEL DAN KONEKTOR
                                Forms\Components\Fieldset::make('KABEL DAN KONEKTOR')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_kondisi_kabel')
                                            ->label('Periksa Kondisi Kabel')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_socket_kabel')
                                            ->label('Periksa Socket Kabel')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_kabel_dan_konektor'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM HIDROLIK
                                Forms\Components\Fieldset::make('SISTEM HIDROLIK')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_kebocoran')
                                            ->label('Periksa Kebocoran')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_tekanan')
                                            ->label('Periksa Tekanan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_hidrolik'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM PEMANAS
                                Forms\Components\Fieldset::make('SISTEM PEMANAS')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_sispemanas_suhu')
                                            ->label('Periksa Suhu')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_kestabilan_pemanas')
                                            ->label('Periksa Kestabilan Pemanas')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_pemanas'),
                                    ])
                                    ->visible(fn(callable $get) => in_array($get('tipe_proses'), ['Tinning', 'Coloring']))
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM PENDINGIN TINNING
                                Forms\Components\Fieldset::make('SISTEM PENDINGIN TINNING')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_kebersihan_kipas')
                                            ->label('Periksa Kebersihan Kipas')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_fungsi_kipas')
                                            ->label('Periksa Fungsi Kipas')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_pendingin_tinning'),
                                    ])
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Tinning')
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM PEWARNA
                                Forms\Components\Fieldset::make('POMPA PEWARNA')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_kebersihan')
                                            ->label('Periksa Kebersihan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_aliran_cairan')
                                            ->label('Periksa Aliran Cairan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_tekanan_coloring')
                                            ->label('Periksa Tekanan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_pewarna'),
                                    ])
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Coloring')
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // SISTEM PENDINGIN MOTOR
                                Forms\Components\Fieldset::make('SISTEM PENDINGIN MOTOR')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_filter')
                                            ->label('Periksa Filter')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_blower')
                                            ->label('Periksa Blower')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('elektrik_sirkulasi')
                                            ->label('Periksa Sirkulasi')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_sistem_pendingin_motor'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // THERMOCOUPLE
                                Forms\Components\Fieldset::make('THERMOCOUPLE')
                                    ->schema([
                                        Forms\Components\Radio::make('elektrik_kalibrasi_suhu')
                                            ->label('Periksa Kalibrasi Suhu')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_elektrik_thermocouple'),
                                    ])
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Extruder')
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            ]),
                        Tabs\Tab::make('MECHANICAL CHECKLIST')
                            ->schema([
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // MECHANICAL CHECKLIST
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // Gearbox
                                Forms\Components\Fieldset::make('Gearbox')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_gearbox_pelumasan')
                                            ->label('Periksa Pelumasaan (Check Level)')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('mekanik_gearbox_kebersihan')
                                            ->label('Periksa Kebersihan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\Radio::make('mekanik_gearbox_suara')
                                            ->label('Periksa Suara')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_mekanik_gearbox'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // Sistem Pendingin
                                Forms\Components\Fieldset::make('Sistem Pendingin')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_sispendingin_aliran_pendingin')
                                            ->label('Periksa Aliran Pendingin')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_sispendingin_kebersihan_pipa')
                                            ->label('Periksa kebersihan Pipa')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_sispendingin_sirkulasi_pipa')
                                            ->label('Periksa Sirkulasi Pipa')
                                            ->inline()
                                            ->required()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ]),
                                        Forms\Components\TextInput::make('remarks_mekanik_sispendingin'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => in_array($get('tipe_proses'), ['Drawing', 'Extruder'])),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // shaft
                                Forms\Components\Fieldset::make('SHAFT')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_shaft_keausan')
                                            ->label('Periksa Keausan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_shaft_kerusakan')
                                            ->label('Periksa Kerusakan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_shaft'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // anealing
                                Forms\Components\Fieldset::make('Anealing')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_anealing_anealing')
                                            ->label('Periksa Anealing')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_anealing_carbon_brush')
                                            ->label('Periksa Carbon Brush')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_anealing'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Drawing'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // rollcap
                                Forms\Components\Fieldset::make('Roll Capstan')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_rollcap_keausan')
                                            ->label('Periksa Keausan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_rollcap_kerusakan')
                                            ->label('Periksa Kerusakan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_rollcap'),
                                    ])
                                    ->columns(1)
                                    ->hidden(fn(callable $get) => in_array($get('tipe_proses'), ['Extruder', 'Coloring'])),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // pulleybelt
                                Forms\Components\Fieldset::make('Pulley dan Belt')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_pulleybelt_kekencangan')
                                            ->label('Periksa Kekencangan Belt')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_pulleybelt_ketebalan')
                                            ->label('Periksa Ketebalan Belt')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_pulleybelt'),
                                    ])
                                    ->columns(1)
                                    ->hidden(fn(callable $get) => $get('tipe_proses') === 'Coloring'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // bearing
                                Forms\Components\Fieldset::make('Bearing')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_bearing_pelumasan')
                                            ->label('Periksa Pelumasan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_bearing_kondisi_fisik')
                                            ->label('Periksa Kondisi Fisik')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_bearing'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // alignmesin
                                Forms\Components\Fieldset::make('Aligment Mesin')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_alignmesin_kesejajaran')
                                            ->label('Periksa Kesejajaran Shaft dan Motor')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_alignmesin'),
                                    ])
                                    ->columns(1),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // gearrantai
                                Forms\Components\Fieldset::make('Gear Rantai')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_gearrantai_pelumasan')
                                            ->label('Periksa Pelumasan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_gearrantai_keausan')
                                            ->label('Periksa Keausan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_gearrantai'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Stranding'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // screewbarel
                                Forms\Components\Fieldset::make('Screew dan Barel')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_screewbarel_kondisi')
                                            ->label('Periksa Kondisi Keausan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_screewbarel_kerusakan')
                                            ->label('Periksa Kerusakan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_screewbarel'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Extruder'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // mesintinning
                                Forms\Components\Fieldset::make('Mesin Tinning')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_mesintinning_kebersihan')
                                            ->label('Periksa Kondisi Kebersihan')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_mesintinning_roller')
                                            ->label('Periksa Kondisi Roller')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_mesintinning'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Tinning'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                // sispencoloring
                                Forms\Components\Fieldset::make('Sistem Pendingin Coloring')
                                    ->schema([
                                        Forms\Components\Radio::make('mekanik_sispencoloring_aliran')
                                            ->label('Periksa Aliran')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_sispencoloring_kebersihan_pipa')
                                            ->label('Periksa Kebersihan Pipa')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\Radio::make('mekanik_sispencoloring_flowmeter_n2')
                                            ->label('Periksa Flowmeter N2')
                                            ->inline()
                                            ->required()
                                            ->options([
                                                'good' => 'Good',
                                                'not good' => 'Not Good'
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('remarks_mekanik_sispencoloring')
                                            ->label('Sistem Pendingin Coloring'),
                                    ])
                                    ->columns(1)
                                    ->visible(fn(callable $get) => $get('tipe_proses') === 'Coloring'),
                                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            ]),
                    ]),
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // Upload Image
                Forms\Components\Section::make()
                    ->description('Upload Gambar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\FileUpload::make('image_before')
                                    ->label('Before')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ]),
                                Forms\Components\FileUpload::make('image_after')
                                    ->label('After')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ]),
                            ]),
                    ]),
                // Forms\Components\Section::make()
                //     ->schema([
                //         SignaturePad::make('signature')
                //             ->label('Tanda Tangan')
                //             ->backgroundColor('#fff')  // Background color on light mode
                //             ->backgroundColorOnDark('#fff')     // Background color on dark mode (defaults to backgroundColor)
                //             ->exportBackgroundColor('#fff')     // Background color on export (defaults to backgroundColor)
                //             ->penColor('#0000FF')                  // Pen color on light mode
                //             ->penColorOnDark('#0000FF')            // Pen color on dark mode (defaults to penColor)
                //             ->exportPenColor('#0000FF')            // Pen color on export (defaults to penColor)
                //             ->required()
                //     ])
                //     ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Di Buat Tanggal')
                //     ->date()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal Pengecekan')
                    ->date()
                    ->sortable()
                    ->extraHeaderAttributes([
                        'class' => 'w-8'
                    ]),
                Tables\Columns\TextColumn::make('nama_operator'),
                Tables\Columns\TextColumn::make('time_start'),
                Tables\Columns\TextColumn::make('time_end'),
                Tables\Columns\TextColumn::make('plant_area')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipe_proses')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_mesin')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('nama_operator'),
                // Tables\Columns\TextColumn::make('hours_meter'),
                // Tables\Columns\TextColumn::make('elektrik_cabon_brush')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('elektrik_suara')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('elektrik_getaran')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('elektrik_suhu')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('elektrik_tahanan_isolasi')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_gearbox_pelumasan')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_gearbox_kebersihan')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_gearbox_suara')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_sistem_pendingin_aliran_pendingin')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_sistem_pendingin_kebersihan_pipa')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('mekanik_sistem_pendingin_sirkulasi_pipa')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->filters([
                //
                // Tables\Filters\Filter::make('created_at')
                //     ->label('Di Buat Tanggal'),
                DateRangeFilter::make('date')
                    ->label('Tanggal Pengecekan'),
                SelectFilter::make('plant_area')
                    ->options([
                        'PLANT A' => 'PLANT A',
                        'PLANT B' => 'PLANT B',
                        'PLANT C' => 'PLANT C',
                        'PLANT D' => 'PLANT D',
                        'PLANT E' => 'PLANT E',
                    ]),
            ],) //layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter'),
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
                // Tables\Actions\Action::make('pdf')
                //     ->openUrlInNewTab()
                //     ->label('PDF')
                //     ->color('success')
                //     ->icon('heroicon-m-arrow-down-on-square')
                //     ->action(function (Checksheet $record) {
                //         return response()->streamDownload(function () use ($record) {
                //             echo Pdf::loadHtml(
                //                 Blade::render('pdf', ['record' => $record])
                //             )->stream();
                //         }, $record->tipe_proses . '.pdf');
                //     }),
                Tables\Actions\Action::make('publish')
                    ->label('Word')
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-on-square')
                    ->url(fn(Checksheet $record) => route('word', $record))
                    // ->visible(fn (Checksheet $record): bool => auth()->user()->can('publish', $record))
                    // ->hidden(fn (Checksheet $record): bool => ! auth()->user()->can('publish_word', $record))
                    // ->authorize('publish_word')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListChecksheets::route('/'),
            // 'create' => Pages\CreateChecksheet::route('/create'),
            // 'view' => Pages\ViewChecksheets::route('/{record}'),
            // 'edit' => Pages\EditChecksheet::route('/{record}/edit'),
        ];
    }
}
