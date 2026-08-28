<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Schedule as SchedulePage;
use App\Filament\Resources\ChecksheetResource;
use App\Models\Checksheet;
use App\Models\Schedule;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WeekPMOverview extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.week-pm-overview';

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public string $filter = 'pending';

    public ?string $plant = null;

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['pending', 'overdue', 'completed'], true)) {
            $this->filter = $filter;
        }
    }

    public function openChecklist(int $scheduleId): void
    {
        abort_unless(auth()->user()?->can('create_checksheet'), 403);

        $schedule = Schedule::query()
            ->with('mesin:id,nama_plant,nama_mesin')
            ->findOrFail($scheduleId);

        session()->put('pm_checksheet_prefill', [
            'plant_area' => $schedule->mesin?->nama_plant ?: $schedule->nama_plant,
            'nama_mesin' => $schedule->mesin?->nama_mesin,
            'date' => Carbon::parse($schedule->rencana_cek, 'Asia/Jakarta')->toDateString(),
        ]);

        $this->redirect(ChecksheetResource::getUrl('index', [
            'action' => 'create',
        ]));
    }

    protected function getViewData(): array
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);

        $schedules = Schedule::query()
            ->with('mesin:id,nama_plant,nama_mesin')
            ->whereBetween('rencana_cek', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->orderBy('rencana_cek')
            ->get();

        $checksheets = Checksheet::query()
            ->whereBetween('date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])
            ->get(['id', 'plant_area', 'nama_mesin', 'date'])
            ->keyBy(fn (Checksheet $checksheet): string => $this->checksheetKey(
                $checksheet->plant_area,
                $checksheet->nama_mesin,
                Carbon::parse($checksheet->date)->toDateString(),
            ));

        $jobs = $schedules->map(function (Schedule $schedule) use ($checksheets, $today): array {
            $plannedDate = Carbon::parse($schedule->rencana_cek, 'Asia/Jakarta')->startOfDay();
            $plant = $schedule->mesin?->nama_plant ?: $schedule->nama_plant;
            $machine = $schedule->mesin?->nama_mesin;
            $key = $this->checksheetKey($plant, $machine, $plannedDate->toDateString());
            $checksheet = $checksheets->get($key);

            if ($checksheet) {
                $status = 'completed';
                $statusLabel = 'Selesai';
                $statusColor = 'success';
                $statusOrder = 4;
            } elseif ($plannedDate->lt($today)) {
                $status = 'overdue';
                $statusLabel = 'Terlambat';
                $statusColor = 'danger';
                $statusOrder = 1;
            } elseif ($plannedDate->isSameDay($today)) {
                $status = 'today';
                $statusLabel = 'Hari ini';
                $statusColor = 'warning';
                $statusOrder = 2;
            } else {
                $status = 'scheduled';
                $statusLabel = 'Terjadwal';
                $statusColor = 'gray';
                $statusOrder = 3;
            }

            $actionUrl = $checksheet
                ? ChecksheetResource::getUrl('index', [
                    'tableAction' => 'view',
                    'tableActionRecord' => $checksheet->getKey(),
                ])
                : null;

            return [
                'id' => $schedule->getKey(),
                'plant' => $plant ?: '-',
                'machine' => $machine ?: '-',
                'planned_date' => $plannedDate,
                'note' => $schedule->keterangan_note,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'status_order' => $statusOrder,
                'action_url' => $actionUrl,
                'action_label' => $checksheet ? 'Lihat hasil' : 'Buka checklist',
                'can_open' => $checksheet
                    ? auth()->user()?->can('view_checksheet')
                    : auth()->user()?->can('create_checksheet'),
            ];
        });

        $plants = $jobs
            ->pluck('plant')
            ->filter(fn (string $plant): bool => $plant !== '-')
            ->unique()
            ->sort()
            ->values();

        $plantJobs = $jobs
            ->when($this->plant, fn (Collection $items): Collection => $items->where('plant', $this->plant))
            ->values();

        $counts = [
            'pending' => $plantJobs->whereIn('status', ['overdue', 'today', 'scheduled'])->count(),
            'overdue' => $plantJobs->where('status', 'overdue')->count(),
            'completed' => $plantJobs->where('status', 'completed')->count(),
        ];

        $filteredJobs = $plantJobs
            ->when(
                $this->filter === 'pending',
                fn (Collection $items): Collection => $items->whereIn('status', ['overdue', 'today', 'scheduled']),
            )
            ->when(
                $this->filter === 'overdue',
                fn (Collection $items): Collection => $items->where('status', 'overdue'),
            )
            ->when(
                $this->filter === 'completed',
                fn (Collection $items): Collection => $items->where('status', 'completed'),
            )
            ->sortBy(fn (array $job): string => sprintf(
                '%d-%s-%s',
                $job['status_order'],
                $job['planned_date']->format('Y-m-d'),
                Str::lower($job['machine']),
            ))
            ->values();

        $totalJobs = $filteredJobs->count();
        $visibleJobs = $filteredJobs
            ->take(8)
            ->values();

        return [
            'jobs' => $visibleJobs,
            'totalJobs' => $totalJobs,
            'counts' => $counts,
            'plants' => $plants,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'scheduleUrl' => SchedulePage::getUrl(),
            'canViewSchedule' => (bool) auth()->user()?->can('page_Schedule'),
        ];
    }

    private function checksheetKey(?string $plant, ?string $machine, string $date): string
    {
        return implode('|', [
            Str::lower(trim((string) $plant)),
            Str::lower(trim((string) $machine)),
            $date,
        ]);
    }
}
