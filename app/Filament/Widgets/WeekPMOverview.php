<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Schedule as SchedulePage;
use App\Filament\Resources\ChecksheetResource;
use App\Models\Checksheet;
use App\Models\Mesin;
use App\Models\Schedule;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WeekPMOverview extends Widget implements HasForms
{
    use HasWidgetShield;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.week-pm-overview';

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public string $viewMode = 'week';

    public string $filter = 'pending';

    public ?string $plant = null;

    public string $calendarDate = '';

    public int $page = 1;

    protected int $perPage = 5;

    public function mount(): void
    {
        $this->calendarDate = now('Asia/Jakarta')->toDateString();

        $this->form->fill([
            'calendarDate' => $this->calendarDate,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('calendarDate')
                    ->hiddenLabel()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->locale('en')
                    ->weekStartsOnMonday()
                    ->closeOnDateSelection()
                    ->live(),
            ]);
    }

    public function setViewMode(string $viewMode): void
    {
        if (! in_array($viewMode, ['agenda', 'week', 'month'], true)) {
            return;
        }

        $this->viewMode = $viewMode;
        $this->page = 1;
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'pending', 'overdue', 'completed'], true)) {
            return;
        }

        $this->filter = $filter;
        $this->page = 1;
    }

    public function updatedPlant(): void
    {
        $this->page = 1;
    }

    public function updatedCalendarDate(): void
    {
        $this->normaliseCalendarDate();
        $this->page = 1;
    }

    public function previousPeriod(): void
    {
        $date = $this->selectedDate();

        $this->calendarDate = ($this->viewMode === 'month'
            ? $date->subMonthNoOverflow()
            : $date->subWeek())
            ->toDateString();

        $this->page = 1;
    }

    public function nextPeriod(): void
    {
        $date = $this->selectedDate();

        $this->calendarDate = ($this->viewMode === 'month'
            ? $date->addMonthNoOverflow()
            : $date->addWeek())
            ->toDateString();

        $this->page = 1;
    }

    public function currentPeriod(): void
    {
        $this->calendarDate = now('Asia/Jakarta')->toDateString();
        $this->page = 1;
    }

    public function showDate(string $date): void
    {
        try {
            $this->calendarDate = Carbon::parse($date, 'Asia/Jakarta')->toDateString();
        } catch (\Throwable) {
            return;
        }

        $this->viewMode = 'agenda';
        $this->page = 1;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
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
        $selectedDate = $this->selectedDate();
        [$rangeStart, $rangeEnd] = $this->dataRange($selectedDate);
        [$calendarStart, $calendarEnd] = $this->calendarRange($selectedDate);

        $schedules = Schedule::query()
            ->with('mesin:id,nama_plant,nama_mesin')
            ->whereBetween('rencana_cek', [
                $rangeStart->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->orderBy('rencana_cek')
            ->get();

        $checksheets = Checksheet::query()
            ->whereBetween('date', [
                $rangeStart->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->get(['id', 'plant_area', 'nama_mesin', 'date', 'time_start', 'time_end'])
            ->keyBy(fn (Checksheet $checksheet): string => $this->checksheetKey(
                $checksheet->plant_area,
                $checksheet->nama_mesin,
                Carbon::parse($checksheet->date)->toDateString(),
            ));

        $jobs = $schedules->map(
            fn (Schedule $schedule): array => $this->mapSchedule($schedule, $checksheets, $today),
        );

        $plants = Mesin::query()
            ->whereNotNull('nama_plant')
            ->where('nama_plant', '!=', '')
            ->distinct()
            ->orderBy('nama_plant')
            ->pluck('nama_plant')
            ->merge($jobs->pluck('plant'))
            ->filter(fn (?string $plant): bool => filled($plant) && $plant !== '-')
            ->unique()
            ->sort()
            ->values();

        $plantJobs = $jobs
            ->when(
                filled($this->plant),
                fn (Collection $items): Collection => $items->where('plant', $this->plant),
            )
            ->values();

        $counts = [
            'all' => $plantJobs->count(),
            'pending' => $plantJobs->whereIn('status', ['overdue', 'today', 'scheduled'])->count(),
            'overdue' => $plantJobs->where('status', 'overdue')->count(),
            'completed' => $plantJobs->where('status', 'completed')->count(),
        ];

        $filteredJobs = $this->filterJobs($plantJobs)
            ->sortBy(fn (array $job): string => sprintf(
                '%s-%d-%s',
                $job['planned_date']->format('Y-m-d'),
                $job['status_order'],
                Str::lower($job['machine']),
            ))
            ->values();

        $jobsByDate = $filteredJobs->groupBy(
            fn (array $job): string => $job['planned_date']->toDateString(),
        );

        $appointmentLimit = $this->viewMode === 'month' ? 2 : 4;
        $calendarDays = $this->buildCalendarDays(
            $calendarStart,
            $calendarEnd,
            $selectedDate,
            $jobsByDate,
            $today,
            $appointmentLimit,
        );

        $totalJobs = $filteredJobs->count();
        $totalPages = max(1, (int) ceil($totalJobs / $this->perPage));
        $currentPage = min(max($this->page, 1), $totalPages);
        $agendaJobs = $filteredJobs
            ->forPage($currentPage, $this->perPage)
            ->values();

        return [
            'viewMode' => $this->viewMode,
            'agendaJobs' => $agendaJobs,
            'calendarDays' => $calendarDays,
            'totalJobs' => $totalJobs,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'fromJob' => $totalJobs === 0 ? 0 : (($currentPage - 1) * $this->perPage) + 1,
            'toJob' => min($currentPage * $this->perPage, $totalJobs),
            'counts' => $counts,
            'plants' => $plants,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'periodLabel' => $this->periodLabel($selectedDate, $rangeStart, $rangeEnd),
            'isCurrentPeriod' => $this->isCurrentPeriod($selectedDate, $rangeStart, $rangeEnd, $today),
            'scheduleUrl' => SchedulePage::getUrl(),
            'canViewSchedule' => (bool) auth()->user()?->can('page_Schedule'),
        ];
    }

    private function mapSchedule(Schedule $schedule, Collection $checksheets, Carbon $today): array
    {
        $plannedDate = Carbon::parse($schedule->rencana_cek, 'Asia/Jakarta')->startOfDay();
        $plant = $schedule->mesin?->nama_plant ?: $schedule->nama_plant;
        $machine = $schedule->mesin?->nama_mesin;
        $checksheet = $checksheets->get(
            $this->checksheetKey($plant, $machine, $plannedDate->toDateString()),
        );

        if ($checksheet) {
            [$status, $statusLabel, $statusColor, $statusOrder] = ['completed', 'Selesai', 'success', 4];
        } elseif ($plannedDate->lt($today)) {
            [$status, $statusLabel, $statusColor, $statusOrder] = ['overdue', 'Terlambat', 'danger', 1];
        } elseif ($plannedDate->isSameDay($today)) {
            [$status, $statusLabel, $statusColor, $statusOrder] = ['today', 'Hari ini', 'warning', 2];
        } else {
            [$status, $statusLabel, $statusColor, $statusOrder] = ['scheduled', 'Terjadwal', 'gray', 3];
        }

        $actualTime = null;
        if ($checksheet?->time_start && $checksheet?->time_end) {
            $actualTime = sprintf(
                '%s–%s',
                Carbon::parse($checksheet->time_start)->format('H:i'),
                Carbon::parse($checksheet->time_end)->format('H:i'),
            );
        }

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
            'actual_time' => $actualTime,
            'action_url' => $checksheet
                ? ChecksheetResource::getUrl('index', [
                    'tableAction' => 'view',
                    'tableActionRecord' => $checksheet->getKey(),
                ])
                : null,
            'can_open' => $checksheet
                ? auth()->user()?->can('view_checksheet')
                : auth()->user()?->can('create_checksheet'),
        ];
    }

    private function filterJobs(Collection $jobs): Collection
    {
        return match ($this->filter) {
            'pending' => $jobs->whereIn('status', ['overdue', 'today', 'scheduled']),
            'overdue' => $jobs->where('status', 'overdue'),
            'completed' => $jobs->where('status', 'completed'),
            default => $jobs,
        };
    }

    private function buildCalendarDays(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        Carbon $selectedDate,
        Collection $jobsByDate,
        Carbon $today,
        int $appointmentLimit,
    ): Collection {
        $days = collect();
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            $date = $cursor->copy();
            $dateJobs = $jobsByDate->get($date->toDateString(), collect())->values();

            $days->push([
                'date' => $date,
                'jobs' => $dateJobs->take($appointmentLimit),
                'total' => $dateJobs->count(),
                'more' => max(0, $dateJobs->count() - $appointmentLimit),
                'is_today' => $date->isSameDay($today),
                'is_selected' => $date->isSameDay($selectedDate),
                'is_current_month' => $date->isSameMonth($selectedDate),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    private function dataRange(Carbon $selectedDate): array
    {
        if ($this->viewMode === 'month') {
            return [
                $selectedDate->copy()->startOfMonth(),
                $selectedDate->copy()->endOfMonth(),
            ];
        }

        return [
            $selectedDate->copy()->startOfWeek(Carbon::MONDAY),
            $selectedDate->copy()->endOfWeek(Carbon::SUNDAY),
        ];
    }

    private function calendarRange(Carbon $selectedDate): array
    {
        if ($this->viewMode === 'month') {
            return [
                $selectedDate->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $selectedDate->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ];
        }

        return $this->dataRange($selectedDate);
    }

    private function periodLabel(Carbon $selectedDate, Carbon $rangeStart, Carbon $rangeEnd): string
    {
        if ($this->viewMode === 'month') {
            return $selectedDate->format('F Y');
        }

        if ($rangeStart->isSameMonth($rangeEnd)) {
            return sprintf(
                '%s–%s',
                $rangeStart->format('d'),
                $rangeEnd->format('d F Y'),
            );
        }

        return sprintf(
            '%s–%s',
            $rangeStart->format('d M'),
            $rangeEnd->format('d M Y'),
        );
    }

    private function isCurrentPeriod(
        Carbon $selectedDate,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        Carbon $today,
    ): bool {
        return $this->viewMode === 'month'
            ? $selectedDate->isSameMonth($today)
            : $today->between($rangeStart, $rangeEnd, true);
    }

    private function selectedDate(): Carbon
    {
        try {
            return Carbon::parse($this->calendarDate ?: 'now', 'Asia/Jakarta')->startOfDay();
        } catch (\Throwable) {
            return now('Asia/Jakarta')->startOfDay();
        }
    }

    private function normaliseCalendarDate(): void
    {
        $this->calendarDate = $this->selectedDate()->toDateString();
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
