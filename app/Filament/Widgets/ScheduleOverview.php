<?php

namespace App\Filament\Widgets;

use App\Models\Checksheet;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;

class ScheduleOverview extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.schedule-overview';
    protected static ?string $description = 'Realisasi Preventive Maintenance Mesin per Plant';
    protected int|string|array $columnSpan = 'full';

    public ?string $plant = null;
    public ?string $date_start = null; // disimpan sebagai string "d/m/Y"
    public ?string $date_end   = null; // disimpan sebagai string "d/m/Y"

    public function mount(): void
    {
        // default: bulan berjalan
        if (!$this->date_start || !$this->date_end) {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $this->form->fill([
                'plant'      => null,
                'date_start' => $start->format('d/m/Y'),
                'date_end'   => $end->format('d/m/Y'),
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('plant')
                ->label('')
                ->placeholder('Semua Plant')
                ->default(null)
                ->prefix('Pilih Plant: ')
                ->options(
                    fn() => Checksheet::query()
                        ->whereNotNull('plant_area')
                        ->distinct()
                        ->orderBy('plant_area')
                        ->pluck('plant_area', 'plant_area')
                        ->toArray()
                )
                ->searchable()
                ->live(debounce: 400),
            Forms\Components\DatePicker::make('date_start')
                ->label('')
                ->native(false)
                ->format('d/m/Y')
                ->prefix('Start Date:')
                ->default(fn() => Carbon::now()->startOfMonth()->format('d/m/Y'))
                ->live(debounce: 400),
            Forms\Components\DatePicker::make('date_end')
                ->label('')
                ->native(false)
                ->format('d/m/Y')
                ->prefix('End Date:')
                ->default(fn() => Carbon::now()->endOfMonth()->format('d/m/Y'))
                ->live(debounce: 400),
            // Forms\Components\Fieldset::make('Filter')
            //     ->schema([])
            //     ->columns(3),
        ])->columns(3);
    }

    protected function getViewData(): array
    {
        // Ambil state terkini dari form
        $state = $this->form->getState();
        $plant = $state['plant'] ?? null;
        $dsStr = $state['date_start'] ?? null; // "d/m/Y"
        $deStr = $state['date_end'] ?? null;   // "d/m/Y"

        // Default ke bulan berjalan jika kosong
        $start = $dsStr ? Carbon::createFromFormat('d/m/Y', $dsStr)->startOfDay()
            : Carbon::now()->startOfMonth();
        $end   = $deStr ? Carbon::createFromFormat('d/m/Y', $deStr)->endOfDay()
            : Carbon::now()->endOfMonth();

        $query = Checksheet::query()
            ->when(filled($plant), fn($q) => $q->where('plant_area', $plant))
            ->when($start && $end, fn($q) => $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]))
            ->orderBy('date')          // urut tetap by date
            ->orderBy('nama_mesin')
            ->select(['id', 'plant_area', 'nama_mesin', 'date']);

        $rows = $query->get();

        // === Durasi preset per jumlah mesin per hari ===
        $hoursPresets = [
            1 => [7.75],
            2 => [3.75, 4.00],
            3 => [2.00, 2.75, 2.58],
            4 => [2.00, 1.25, 2.00, 2.00],
        ];

        $tasks = collect();

        // Kelompokkan per tanggal (YYYY-MM-DD)
        $rows->groupBy(fn($r) => Carbon::parse($r->date)->format('Y-m-d'))
            ->each(function ($group) use ($hoursPresets, $tasks) {
                $count = $group->count();
                $hours = $hoursPresets[$count] ?? array_fill(0, $count, 1);

                foreach ($group->values() as $i => $r) {
                    $iso = Carbon::parse($r->date)->format('Y-m-d');

                    $tasks->push([
                        'id'         => $r->id,
                        'text'       => (string) $r->nama_mesin,   // Mesin
                        'plant'      => (string) $r->plant_area,   // Plant (kolom grid)
                        'start_date' => $iso,                      // Blade akan tambahkan " 08:00"
                        'duration'   => (float)($hours[$i] ?? 1),  // JAM (karena work_time + duration_unit=hour)
                    ]);
                }
            });

        return ['tasks' => $tasks->values()];
    }

    // protected function getViewData(): array
    // {
    //     $rows = \App\Models\Checksheet::query()
    //         ->when(filled($this->plant), fn($q) => $q->where('plant_area', $this->plant))
    //         ->orderBy('date')
    //         ->orderBy('nama_mesin')
    //         ->get(['id', 'plant_area', 'nama_mesin', 'date']);

    //     $durasiPresets = [
    //         1 => [7.75],
    //         2 => [3.75, 4.00],
    //         3 => [2.00, 2.75, 2.58],
    //         4 => [2.00, 1.25, 2.00, 2.00],
    //     ];

    //     $tasks = collect();

    //     $rows->groupBy(fn($r) => \Carbon\Carbon::parse($r->date)->format('Y-m-d'))
    //         ->each(function ($group) use ($durasiPresets, $tasks) {
    //             $count = $group->count();
    //             $durations = $durasiPresets[$count] ?? array_fill(0, $count, 1);

    //             foreach ($group->values() as $idx => $r) {
    //                 $iso = \Carbon\Carbon::parse($r->date)->format('Y-m-d');

    //                 $tasks->push([
    //                     'id'         => $r->id,
    //                     'text'       => (string) $r->nama_mesin,
    //                     'plant'      => (string) $r->plant_area,
    //                     'start_date' => $iso,
    //                     'duration'   => $durations[$idx] ?? 1,
    //                 ]);
    //             }
    //         });

    //     return ['tasks' => $tasks->values()];
    // }


    // protected function getViewData(): array
    // {
    //     $plant = $this->plant;

    //     $rows = \App\Models\Checksheet::query()
    //         ->when(filled($plant), fn($q) => $q->where('plant_area', $plant)) // <-- hanya filter kalau dipilih
    //         ->orderBy('date') 
    //         ->get(['id', 'plant_area', 'nama_mesin', 'date']);

    //     $tasks = $rows->map(function ($r) {
    //         $iso = \Carbon\Carbon::parse($r->date)->format('Y-m-d');

    //         return [
    //             'id'         => $r->id,
    //             'text'       => (string) $r->nama_mesin,
    //             'plant'      => (string) $r->plant_area,
    //             'start_date' => $iso,
    //             'duration'   => 1,
    //         ];
    //     })->values();

    //     return compact('tasks');
    // }
}
