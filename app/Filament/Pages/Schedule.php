<?php

namespace App\Filament\Pages;

use App\Models\Mesin;
use App\Models\Schedule as ModelsSchedule;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament Page: Schedule Preventive
 *
 * Catatan:
 * - Semua logika dipertahankan persis, hanya perapihan struktur & komentar.
 * - Bagian-bagian besar diberi header agar mudah navigasi & menambah fungsi baru.
 */
class Schedule extends Page implements HasForms
{
    use InteractsWithForms;

    // -------------------------------------------------------------------------
    //  Static Page Config
    // -------------------------------------------------------------------------

    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Schedule';
    protected static ?string $title           = 'Schedule Preventive';
    protected static ?string $slug            = 'schedule';
    protected static string  $view            = 'filament.pages.schedule';

    // -------------------------------------------------------------------------
    //  Public State
    // -------------------------------------------------------------------------

    /** Preview hasil duplicate (untuk modal review). */
    public array $dupPreview = [];

    /** Argumen terakhir saat klik Preview duplicate (disimpan untuk Submit). */
    public array $dupPreviewArgs = [];

    /** Rentang tanggal filter (disimpan String "d/m/Y"). */
    public ?string $date_start = null;
    public ?string $date_end   = null;
    public ?string $exportDate = null;

    // -------------------------------------------------------------------------
    //  Constants & Rules
    // -------------------------------------------------------------------------

    /**
     * Aturan jam per jumlah task pada tanggal yang sama (GLOBAL lintas plant).
     * Index kunci = jumlah task pada satu tanggal, nilai = array alokasi jam tiap urutan.
     */
    private const WORKLOAD_RULES = [
        1 => [7.75],
        2 => [3.75, 4.00],
        3 => [2.00, 2.75, 2.58],
        4 => [2.00, 1.25, 2.00, 2.00],
    ];

    /** Parse "d/m/Y" ke "Y-m-d" (atau null jika kosong/invalid). */
    private function toYmd(?string $dmy): ?string
    {
        if (empty($dmy)) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $dmy)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    //  Lifecycle
    // -------------------------------------------------------------------------

    /**
     * Saat mount: set default rentang tanggal ke bulan berjalan bila belum diisi.
     */
    public function mount(): void
    {
        $this->exportDate = now()
            ->locale('id')
            ->timezone('Asia/Jakarta')
            ->translatedFormat('j F Y');

        if (!$this->date_start || !$this->date_end) {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $this->form->fill([
                'date_start' => $start->format('d/m/Y'),
                'date_end'   => $end->format('d/m/Y'),
            ]);
        }
        $this->dispatch('schedule-reload');
    }

    // -------------------------------------------------------------------------
    //  Form (Filter Rentang Tanggal)
    // -------------------------------------------------------------------------

    /**
     * Form filter tanggal (start/end) di header page.
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date_start')
                ->label('')
                ->native(false)
                ->format('d/m/Y')
                ->prefix('Start Date:')
                ->default(fn() => Carbon::now()->startOfMonth()->format('d/m/Y'))
                ->dehydrated(true)
                ->live(debounce: 400)
                ->afterStateUpdated(fn() => $this->dispatch('schedule-reload')),

            Forms\Components\DatePicker::make('date_end')
                ->label('')
                ->native(false)
                ->format('d/m/Y')
                ->prefix('End Date:')
                ->default(fn() => Carbon::now()->endOfMonth()->format('d/m/Y'))
                ->dehydrated(true)
                ->live(debounce: 400)
                ->afterStateUpdated(fn() => $this->dispatch('schedule-reload')),
        ])->columns(2);
    }


    // -------------------------------------------------------------------------
    //  Gantt Payload Builder
    // -------------------------------------------------------------------------

    /**
     * Build payload untuk komponen Gantt:
     * - data: parent virtual per plant (id negatif) + tasks anak
     * - resources: daftar plant unik
     * - links: dependency dari kolom pred_ids
     * - tiap task anak ditambah field 'hours' (hasil aturan global per tanggal)
     *
     * Tanggal dipastikan format 'Y-m-d' (date-only).
     */
    public function getGanttPayload(): array
    {
        // --- BACA RENTANG TANGGAL DARI FORM STATE ---
        $formState = $this->form->getState();
        $startYmd  = $this->toYmd($formState['date_start'] ?? $this->date_start);
        $endYmd    = $this->toYmd($formState['date_end']   ?? $this->date_end);

        // --- BASE QUERY (EAGER LOAD MESIN) ---
        $q = ModelsSchedule::query()
            ->with(['mesin:id,nama_mesin'])
            ->orderBy('nama_plant')
            ->orderBy('rencana_cek')
            ->orderBy(
                Mesin::select('nama_mesin')
                    ->whereColumn('mesins.id', 'schedules.mesin_id')
                    ->limit(1)
            )
            ->orderBy('id');

        // --- FILTER TANGGAL ---
        if ($startYmd && $endYmd) {
            $q->whereBetween('rencana_cek', [$startYmd, $endYmd]);
        } elseif ($startYmd) {
            $q->whereDate('rencana_cek', '>=', $startYmd);
        } elseif ($endYmd) {
            $q->whereDate('rencana_cek', '<=', $endYmd);
        }

        $rows = $q->get();

        // ---------- Resources (Plants) ----------
        $plants          = $rows->pluck('nama_plant')->filter()->unique()->values();
        $resources       = [];
        $plantResourceId = [];
        foreach ($plants as $i => $plant) {
            $rid = (string) ($i + 1);
            $plantResourceId[$plant] = $rid;
            $resources[] = ['key' => $rid, 'label' => (string) $plant];
        }

        // ---------- Virtual Parents per Plant ----------
        $parents     = [];
        $parentIdMap = [];
        foreach ($plants as $i => $plant) {
            $pid = - ($i + 1);
            $parentIdMap[$plant] = $pid;
            $parents[] = ['id' => $pid, 'text' => (string) $plant, 'type' => 'project', 'open' => true];
        }

        // ---------- Child Tasks ----------
        $tasks = [];
        foreach ($rows as $r) {
            $start = optional($r->rencana_cek)?->format('Y-m-d');
            $end   = $start ? \Illuminate\Support\Carbon::parse($start)->addDay()->format('Y-m-d') : null;

            // ambil nama mesin dari relasi; jika tidak ada, beri placeholder
            $namaMesin = $r->mesin?->nama_mesin ?? '(Mesin tidak ditemukan)';

            $tasks[] = [
                'id'         => (int) $r->id,
                'text'       => (string) $namaMesin,                 // <— dari relasi
                'start_date' => $start,
                'end_date'   => $end,
                'duration'   => ($start && $end) ? 1 : 0,
                'parent'     => $parentIdMap[$r->nama_plant] ?? 0,
                'plant'      => (string) $r->nama_plant,
                'user'       => $plantResourceId[$r->nama_plant] ?? null,
                'keterangan' => (string) $r->keterangan,
            ];
        }

        // ---------- Assign Hours ----------
        $tasks = $this->assignGlobalWorkloadHours($tasks);

        foreach ($tasks as &$t) {
            if (($t['type'] ?? '') !== 'project' && ($t['hours'] ?? 0) === 0.0 && !empty($t['start_date'])) {
                $t['__over_limit'] = true;
            }
        }
        unset($t);

        // ---------- Links ----------
        $links  = [];
        $autoId = 1;
        foreach ($rows as $r) {
            $preds = (array) ($r->pred_ids ?? []);
            foreach ($preds as $p) {
                $src  = (int) ($p['id']   ?? 0);
                $type = (string) ($p['type'] ?? '0');
                if ($src > 0) {
                    $links[] = [
                        'id'     => $autoId++,
                        'source' => $src,
                        'target' => (int) $r->id,
                        'type'   => $type,
                    ];
                }
            }
        }

        return [
            'data'      => array_merge($parents, $tasks),
            'resources' => $resources,
            'links'     => $links,
        ];
    }

    // -------------------------------------------------------------------------
    //  Workload Assignment (Jam per Tanggal)
    // -------------------------------------------------------------------------

    /**
     * Tetapkan 'hours' ke setiap task anak, berdasarkan aturan global per tanggal (lintas plant).
     * Urutan deterministik per tanggal: nama_mesin ASC, nama_plant ASC, id ASC.
     *
     * @param  array<int, array<string,mixed>>  $tasks
     * @return array<int, array<string,mixed>>
     */
    private function assignGlobalWorkloadHours(array $tasks): array
    {
        // Group by start_date
        $byDate = [];
        foreach ($tasks as $t) {
            $d = (string) ($t['start_date'] ?? '');
            if (! $d) {
                continue;
            }
            $byDate[$d] ??= [];
            $byDate[$d][] = $t;
        }

        // Hitung hoursById sesuai aturan WORKLOAD_RULES
        $hoursById = [];

        foreach ($byDate as $ymd => $items) {
            // Urutan deterministik
            usort($items, function ($a, $b) {
                $tx = strcmp((string) ($a['text'] ?? ''), (string) ($b['text'] ?? ''));
                if ($tx !== 0) return $tx;

                $pl = strcmp((string) ($a['plant'] ?? ''), (string) ($b['plant'] ?? ''));
                if ($pl !== 0) return $pl;

                return ($a['id'] <=> $b['id']);
            });

            $n    = count($items);
            $rule = self::WORKLOAD_RULES[$n] ?? null;

            if ($n > 4 || ! $rule) {
                // Over 4 task/hari => 0 jam
                foreach ($items as $it) {
                    $hoursById[(int) $it['id']] = 0.0;
                }
                continue;
            }

            foreach ($items as $idx => $it) {
                $hoursById[(int) $it['id']] = (float) ($rule[$idx] ?? 0.0);
            }
        }

        // Tambahkan hours ke masing-masing task
        foreach ($tasks as &$t) {
            $tid       = (int) ($t['id'] ?? 0);
            $t['hours'] = array_key_exists($tid, $hoursById) ? $hoursById[$tid] : 0.0;
        }
        unset($t);

        return $tasks;
    }

    // -------------------------------------------------------------------------
    //  Validation Helpers
    // -------------------------------------------------------------------------

    /**
     * Validasi GLOBAL: maksimal 4 task pada tanggal $date (Y-m-d), lintas plant.
     */
    private function exceedsGlobalDailyLimit(string $date, ?int $excludeId = null): bool
    {
        $q = ModelsSchedule::query()->whereDate('rencana_cek', '=', $date);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        $count = $q->count();
        return $count >= 4;
    }

    /**
     * Geser tanggal ke hari kerja berikutnya bila jatuh di Sabtu/Minggu.
     */
    private function nextWorkday(string $ymd): string
    {
        $d = Carbon::parse($ymd);
        while (in_array($d->dayOfWeek, [0, 6], true)) { // 0=Min, 6=Sab
            $d->addDay();
        }
        return $d->format('Y-m-d');
    }

    /**
     * Bersihkan state preview duplicate.
     */
    private function clearDupPreview(): void
    {
        $this->dupPreview     = [];
        $this->dupPreviewArgs = [];
    }

    // -------------------------------------------------------------------------
    //  Header Actions
    // -------------------------------------------------------------------------

    /**
     * Header actions (Tambah Jadwal & Duplicate Tahun).
     */
    protected function getHeaderActions(): array
    {
        return [
            // -------------------- Add/Create Task --------------------
            Action::make('create')
                ->label('Create Schedule')
                ->modalHeading('Create a Check Schedule')
                ->icon('heroicon-m-plus')
                ->closeModalByEscaping(false)
                ->closeModalByClickingAway(false)
                ->modalWidth(MaxWidth::Medium)
                ->slideOver()
                ->color('primary')
                // ->model(\App\Models\Schedule::class)
                ->form(function (Form $form) {
                    return $form
                        ->model(\App\Models\Schedule::class)
                        ->schema([
                            Forms\Components\Select::make('nama_plant')
                                ->label('Nama Plant')
                                ->native(false)
                                ->options([
                                    'PLANT A' => 'PLANT A',
                                    'PLANT B' => 'PLANT B',
                                    'PLANT C' => 'PLANT C',
                                    'PLANT D' => 'PLANT D',
                                    'PLANT E' => 'PLANT E',
                                ])
                                ->live()
                                ->required(),

                            Forms\Components\Select::make('mesin_id')
                                ->label('Nama Mesin')
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->disabled(fn(Get $get) => blank($get('nama_plant')))
                                ->helperText(fn(Get $get) => blank($get('nama_plant')) ? 'Select the plant first' : null)
                                ->required()
                                ->options(function (Get $get) {
                                    $plant = $get('nama_plant');

                                    if (blank($plant)) {
                                        return [];
                                    }

                                    return Mesin::query()
                                        ->where('nama_plant', $plant)       // sesuaikan jika kolom berbeda
                                        ->orderBy('nama_mesin')             // ganti ke 'nama' jika nama kolomnya 'nama'
                                        ->pluck('nama_mesin', 'id')         // tampilkan nama, simpan id
                                        ->toArray();
                                }),

                            Forms\Components\DatePicker::make('rencana_cek')
                                ->label('Rencana Cek')
                                ->required()
                                ->native(false),

                            Forms\Components\Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->columnSpanFull(),
                        ]);
                })
                ->action(function (array $data) {
                    $date = optional(Arr::get($data, 'rencana_cek'))
                        ? Carbon::parse($data['rencana_cek'])->format('Y-m-d')
                        : null;

                    if ($date && $this->exceedsGlobalDailyLimit($date, null)) {
                        Notification::make()
                            ->title("Gagal: Maksimal 4 mesin pada $date")
                            ->danger()
                            ->send();
                        return;
                    }

                    // [PATCH] Bila ada kolom keterangan_note, pakai itu; jika tidak, biarkan 'keterangan'
                    try {
                        $table = (new ModelsSchedule())->getTable();

                        if (Schema::hasColumn($table, 'keterangan_note') && array_key_exists('keterangan', $data)) {
                            $data['keterangan_note'] = (string) $data['keterangan'];
                            unset($data['keterangan']);
                        }
                    } catch (\Throwable $e) {
                        // fallback: gunakan 'keterangan' apa adanya
                    }

                    ModelsSchedule::create($data);

                    Notification::make()
                        ->title('Jadwal berhasil dibuat')
                        ->success()
                        ->send();

                    $this->dispatch('schedule-reload');
                }),

            // -------------------- Duplicate Year --------------------
            Action::make('duplicateYear')
                ->label('Duplicate')
                ->icon('heroicon-m-document-duplicate')
                ->color('gray')
                ->closeModalByEscaping(false)
                ->closeModalByClickingAway(false)
                ->modalWidth(MaxWidth::Medium)
                ->slideOver()
                ->modalHeading('Duplicate Schedule per year')
                ->modalDescription('Gandakan semua jadwal dari tahun sumber. Aktifkan fitur Weekend jika ingin menggeser (otomatis) ke hari kerja.')
                ->form(function () {
                    $plants   = ModelsSchedule::query()
                        ->select('nama_plant')
                        ->distinct()
                        ->orderBy('nama_plant')
                        ->pluck('nama_plant', 'nama_plant')
                        ->toArray();

                    $thisYear = (int) now()->format('Y');

                    return [
                        Forms\Components\Select::make('source_year')
                            ->label('Tahun Sumber')
                            ->options([
                                $thisYear - 1 => $thisYear - 1,
                                $thisYear     => $thisYear,
                                $thisYear + 1 => $thisYear + 1,
                            ])
                            ->required()
                            ->default($thisYear)
                            ->native(false),

                        Forms\Components\Select::make('plants')
                            ->label('Filter Plant (opsional)')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Jika dipilih, hanya plant terpilih yang akan digandakan.')
                            ->options($plants)
                            ->multiple()
                            ->native(false),

                        Forms\Components\Toggle::make('shift_weekend')
                            ->label('Geser weekend ke hari kerja')
                            ->default(true),

                        Forms\Components\Toggle::make('copy_notes')
                            ->label('Salin catatan')
                            ->default(true),
                    ];
                })
                ->modalSubmitActionLabel('Preview')
                ->extraModalFooterActions([
                    // ---------- Modal Anak: Review & Submit ----------
                    Action::make('reviewAndRun')
                        ->label('Submit')
                        ->color('primary')
                        ->closeModalByEscaping(false)
                        ->closeModalByClickingAway(false)
                        ->slideOver()
                        ->disabled(fn() => empty($this->dupPreviewArgs)) // aktif setelah Preview ditekan
                        ->modalHeading('Preview & Konfirmasi')
                        ->modalContent(function () {
                            if (empty($this->dupPreview)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm text-gray-600">Belum ada preview. Tutup modal ini lalu klik <strong>Preview</strong> dulu.</p>'
                                );
                            }

                            $p = $this->dupPreview;

                            $srcYear      = (int) ($p['srcYear'] ?? 0);
                            $tgtYear      = (int) ($p['tgtYear'] ?? 0);
                            $totalSource  = (int) ($p['totalSource'] ?? 0);
                            $canCreate    = (int) ($p['canCreate'] ?? 0);
                            $withoutDate  = (int) ($p['withoutDate'] ?? 0);
                            $skippedDates = count($p['perDateSkipped'] ?? []);
                            $shiftWeekend = ! empty($p['shiftWeekend']) ? 'YA' : 'TIDAK';
                            $copyNotes    = ! empty($p['copyNotes']) ? 'YA' : 'TIDAK';

                            $rows     = '';
                            $perPlant = $p['perPlant'] ?? [];
                            $examples = $p['perPlantExamples'] ?? [];

                            ksort($perPlant);

                            foreach ($perPlant as $plant => $count) {
                                $ex   = implode(', ', $examples[$plant] ?? []);
                                $rows .= '<tr>'
                                    . '<td class="px-3 py-1 font-medium text-gray-800">' . e($plant) . '</td>'
                                    . '<td class="px-3 py-1 text-gray-700">' . e($count) . '</td>'
                                    . '<td class="px-3 py-1 text-gray-700">' . e($ex ?: '-') . '</td>'
                                    . '</tr>';
                            }

                            $html = <<<HTML
                                <div class="space-y-3 text-sm text-gray-700">
                                  <div class="bg-gray-50 rounded-lg p-3">
                                    <div><strong>Preview:</strong> {$srcYear} → {$tgtYear}</div>
                                    <div>Kandidat: <strong>{$totalSource}</strong> • Bisa dibuat: <strong>{$canCreate}</strong> • Tanpa tanggal: <strong>{$withoutDate}</strong> • Skip (penuh): <strong>{$skippedDates}</strong></div>
                                    <div>Shift weekend: <strong>{$shiftWeekend}</strong> • Copy notes: <strong>{$copyNotes}</strong></div>
                                  </div>

                                  <div class="overflow-hidden border border-gray-200 rounded-lg">
                                    <table class="w-full text-sm">
                                      <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                                        <tr>
                                          <th class="px-3 py-1 text-left">Plant</th>
                                          <th class="px-3 py-1 text-left">Jumlah Task</th>
                                          <th class="px-3 py-1 text-left">Mesin</th>
                                        </tr>
                                      </thead>
                                      <tbody class="divide-y divide-gray-100 bg-white">
                                        {$rows}
                                      </tbody>
                                    </table>
                                  </div>

                                  <p class="text-gray-600 mt-1">
                                    Sebanyak <strong>{$canCreate}</strong> jadwal akan dibuat di tahun <strong>{$tgtYear}</strong>. Jadwal pada tanggal penuh akan otomatis dilewati.
                                  </p>
                                </div>
                            HTML;

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->requiresConfirmation()
                        ->action(function () {
                            // Eksekusi duplikasi menggunakan state dupPreviewArgs
                            $srcYear      = (int) ($this->dupPreviewArgs['source_year'] ?? (int) now()->format('Y'));
                            $tgtYear      = $srcYear + 1;
                            $plantsFilter = (array) ($this->dupPreviewArgs['plants'] ?? []);
                            $shiftWeekend = (bool) ($this->dupPreviewArgs['shift_weekend'] ?? true);
                            $copyNotes    = (bool) ($this->dupPreviewArgs['copy_notes'] ?? true);

                            $q = ModelsSchedule::query()
                                ->with('mesin')
                                ->whereYear('rencana_cek', $srcYear);
                            if ($plantsFilter) $q->whereIn('nama_plant', $plantsFilter);
                            $rows = $q->orderBy('rencana_cek')->get();

                            $created       = 0;
                            $skippedFull   = 0;
                            $skippedNoDate = 0;

                            foreach ($rows as $r) {
                                $base = optional($r->rencana_cek)?->format('Y-m-d');
                                if (!$base) {
                                    $skippedNoDate++;
                                    continue;
                                }

                                $d   = Carbon::parse($base);
                                $tgt = Carbon::create($tgtYear, $d->month, $d->day)->format('Y-m-d');
                                if ($shiftWeekend) $tgt = $this->nextWorkday($tgt);
                                if ($this->exceedsGlobalDailyLimit($tgt, null)) {
                                    $skippedFull++;
                                    continue;
                                }

                                ModelsSchedule::create([
                                    'nama_plant'      => $r->nama_plant,
                                    'mesin_id'        => $r->mesin_id,      // <— FK ikut disalin
                                    'rencana_cek'     => $tgt,
                                    'keterangan_note' => $copyNotes ? $r->keterangan_note : null,
                                ]);

                                $created++;
                            }

                            Notification::make()
                                ->title("Duplikasi {$srcYear} → {$tgtYear}")
                                ->body("Dibuat: {$created}. Dilewati: {$skippedFull}. Tanpa tanggal: {$skippedNoDate}.")
                                ->success()
                                ->send();

                            $this->dupPreview     = [];
                            $this->dupPreviewArgs = [];
                            $this->dispatch('schedule-reload');
                        })
                        ->cancelParentActions('duplicateYear'), // tutup & batalkan modal parent
                ])
                ->action(function (array $data, Action $action) {
                    // Hitung preview & simpan state untuk modal Review
                    $srcYear      = (int) ($data['source_year'] ?? (int) now()->format('Y'));
                    $tgtYear      = $srcYear + 1;
                    $plantsFilter = (array) ($data['plants'] ?? []);
                    $shiftWeekend = (bool) ($data['shift_weekend'] ?? true);
                    $copyNotes    = (bool) ($data['copy_notes'] ?? true);

                    $q = ModelsSchedule::query()
                        ->with('mesin')
                        ->whereYear('rencana_cek', $srcYear);
                    if ($plantsFilter) $q->whereIn('nama_plant', $plantsFilter);
                    $rows = $q->orderBy('rencana_cek')->get();

                    if ($rows->isEmpty()) {
                        Notification::make()
                            ->title("Tidak ada data pada tahun {$srcYear}")
                            ->warning()
                            ->send();
                        return;
                    }

                    $perPlant          = [];
                    $perPlantExamples  = [];
                    $perDateCreate     = [];
                    $perDateSkipped    = [];
                    $withoutDate       = 0;
                    $canCreate         = 0;

                    foreach ($rows as $r) {
                        $base = optional($r->rencana_cek)?->format('Y-m-d');
                        if (! $base) {
                            $withoutDate++;
                            continue;
                        }

                        $d   = Carbon::parse($base);
                        $tgt = Carbon::create($tgtYear, $d->month, $d->day)->format('Y-m-d');

                        if ($shiftWeekend) {
                            $tgt = $this->nextWorkday($tgt);
                        }

                        if ($this->exceedsGlobalDailyLimit($tgt, null)) {
                            $perDateSkipped[$tgt] = ($perDateSkipped[$tgt] ?? 0) + 1;
                            continue;
                        }

                        $canCreate++;
                        $perDateCreate[$tgt] = ($perDateCreate[$tgt] ?? 0) + 1;

                        $plant                = (string) $r->nama_plant;
                        $perPlant[$plant]     = ($perPlant[$plant] ?? 0) + 1;
                        $perPlantExamples[$plant][] = $r->mesin->nama_mesin ?? $r->nama_mesin;

                        if (count($perPlantExamples[$plant]) < 3) {
                            $perPlantExamples[$plant][] = (string) $r->nama_mesin;
                        }
                    }

                    $this->dupPreview = [
                        'srcYear'          => $srcYear,
                        'tgtYear'          => $tgtYear,
                        'totalSource'      => $rows->count(),
                        'canCreate'        => $canCreate,
                        'withoutDate'      => $withoutDate,
                        'perPlant'         => $perPlant,
                        'perPlantExamples' => $perPlantExamples,
                        'perDateCreate'    => $perDateCreate,
                        'perDateSkipped'   => $perDateSkipped,
                        'shiftWeekend'     => $shiftWeekend,
                        'copyNotes'        => $copyNotes,
                        'filterCount'      => count($plantsFilter),
                    ];

                    $this->dupPreviewArgs = [
                        'source_year'   => $srcYear,
                        'plants'        => $plantsFilter,
                        'shift_weekend' => $shiftWeekend,
                        'copy_notes'    => $copyNotes,
                    ];

                    // Tahan modal parent agar user bisa klik "Submit" di modal anak
                    $action->halt();
                }),
        ];
    }

    // -------------------------------------------------------------------------
    //  Task Inline Updates (Drag/Drop dsb. via Alpine/JS)
    // -------------------------------------------------------------------------

    /**
     * Dipanggil dari JS (Alpine) saat drag selesai. Menyimpan tanggal baru; validasi global 4 task/hari.
     */
    public function updateTask(array $payload): void
    {
        $id    = (int) Arr::get($payload, 'id');
        $start = (string) Arr::get($payload, 'start_date');

        if (! $id || ! $start) {
            Notification::make()->title('Gagal menyimpan: data tidak lengkap')->danger()->send();
            return;
        }

        $model = ModelsSchedule::find($id);
        if (! $model) {
            Notification::make()->title('Jadwal tidak ditemukan')->danger()->send();
            return;
        }

        if ($this->exceedsGlobalDailyLimit($start, $id)) {
            Notification::make()->title("Pindah gagal: Maksimal 4 mesin pada $start")->danger()->send();
            return;
        }

        $model->update(['rencana_cek' => $start]);

        Notification::make()->title('Jadwal dipindah ke ' . $start)->success()->send();
    }

    // -------------------------------------------------------------------------
    //  Link / Dependency Handlers
    // -------------------------------------------------------------------------

    /**
     * Tambah link dependency (onAfterLinkAdd).
     */
    public function addLink(array $payload): void
    {
        $source = (int) ($payload['source'] ?? 0);
        $target = (int) ($payload['target'] ?? 0);
        $type   = (string) ($payload['type']   ?? '0');

        if (! $source || ! $target || $source === $target) {
            Notification::make()->title('Link tidak valid')->danger()->send();
            return;
        }

        $tgt = ModelsSchedule::find($target);
        if (! $tgt) {
            Notification::make()->title('Task target tidak ditemukan')->danger()->send();
            return;
        }

        $tgt->addPred($source, $type);
        $tgt->save();

        Notification::make()->title('Dependency disimpan')->success()->send();
    }

    /**
     * Hapus link dependency (onAfterLinkDelete).
     */
    public function deleteLink(array $payload): void
    {
        $source = (int) ($payload['source'] ?? 0);
        $target = (int) ($payload['target'] ?? 0);

        if (! $source || ! $target) {
            Notification::make()->title('Data link tidak lengkap')->danger()->send();
            return;
        }

        $tgt = ModelsSchedule::find($target);
        if (! $tgt) {
            Notification::make()->title('Task target tidak ditemukan')->danger()->send();
            return;
        }

        $tgt->removePred($source);
        $tgt->save();

        Notification::make()->title('Dependency dihapus')->success()->send();
    }

    /**
     * Update link dependency (onAfterLinkUpdate).
     */
    public function updateLink(array $payload): void
    {
        $source = (int) ($payload['source'] ?? 0);
        $target = (int) ($payload['target'] ?? 0);
        $type   = (string) ($payload['type']   ?? '0');

        if (! $source || ! $target) {
            Notification::make()->title('Data link tidak lengkap')->danger()->send();
            return;
        }

        $tgt = ModelsSchedule::find($target);
        if (! $tgt) {
            Notification::make()->title('Task target tidak ditemukan')->danger()->send();
            return;
        }

        $preds = collect($tgt->pred_ids)
            ->map(function ($p) use ($source, $type) {
                if ((int) ($p['id'] ?? 0) === $source) {
                    return ['id' => $source, 'type' => $type];
                }
                return $p;
            })
            ->values()
            ->all();

        $tgt->pred_ids = $preds;
        $tgt->save();

        Notification::make()->title('Dependency diupdate')->success()->send();
    }

    // -------------------------------------------------------------------------
    //  Row Actions (Edit/Delete)
    // -------------------------------------------------------------------------

    /**
     * Action: Edit Task (dibuka dari JS via openEditAction()).
     */

    public function editTaskAction(): Action
    {
        return Action::make('editTask')
            ->model(\App\Models\Schedule::class) // penting untuk relationship() (kalau dipakai)
            ->modalHeading('Edit Jadwal Cek')
            ->closeModalByEscaping(false)
            ->closeModalByClickingAway(false)
            ->slideOver()
            ->modalWidth(MaxWidth::Medium)
            ->form([
                Forms\Components\Select::make('nama_plant')
                    ->label('Nama Plant')
                    ->native(false)
                    ->options([
                        'PLANT A' => 'PLANT A',
                        'PLANT B' => 'PLANT B',
                        'PLANT C' => 'PLANT C',
                        'PLANT D' => 'PLANT D',
                        'PLANT E' => 'PLANT E',
                    ])
                    ->live()
                    ->required(),

                Forms\Components\Select::make('mesin_id')
                    ->label('Nama Mesin')
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->disabled(fn(Get $get) => blank($get('nama_plant')))
                    ->helperText(fn(Get $get) => blank($get('nama_plant')) ? 'Pilih plant terlebih dahulu' : null)
                    ->required()
                    ->options(function (Get $get) {
                        $plant = $get('nama_plant');

                        if (blank($plant)) {
                            return [];
                        }

                        return Mesin::query()
                            ->where('nama_plant', $plant)       // sesuaikan jika kolom berbeda
                            ->orderBy('nama_mesin')             // ganti ke 'nama' jika nama kolomnya 'nama'
                            ->pluck('nama_mesin', 'id')         // tampilkan nama, simpan id
                            ->toArray();
                    }),

                Forms\Components\DatePicker::make('rencana_cek')->label('Rencana Cek')->required()->native(false),
                Forms\Components\Textarea::make('keterangan_note')->label('Keterangan')->columnSpanFull(),
            ])
            ->mountUsing(function (Forms\Form $form, array $arguments) {
                $m = ModelsSchedule::with('mesin')->find((int)($arguments['id'] ?? 0));
                if ($m) {
                    $form->fill([
                        'nama_plant'      => $m->nama_plant,
                        'mesin_id'        => $m->mesin_id,
                        'rencana_cek'     => optional($m->rencana_cek)?->format('Y-m-d'),
                        'keterangan_note' => $m->keterangan_note,
                    ]);
                }
            })
            ->action(function (array $data, array $arguments) {
                $id = (int)($arguments['id'] ?? 0);
                $m  = ModelsSchedule::find($id);
                if (! $m) {
                    Notification::make()->title('Jadwal tidak ditemukan')->danger()->send();
                    return;
                }

                $newDate = isset($data['rencana_cek']) ? \Illuminate\Support\Carbon::parse($data['rencana_cek'])->format('Y-m-d') : null;
                if ($newDate && $this->exceedsGlobalDailyLimit($newDate, $id)) {
                    Notification::make()->title("Gagal: Maksimal 4 mesin pada {$newDate}")->danger()->send();
                    return;
                }

                $m->update([
                    'nama_plant'      => $data['nama_plant'],
                    'mesin_id'        => $data['mesin_id'],
                    'rencana_cek'     => $newDate,
                    'keterangan_note' => $data['keterangan_note'] ?? null,
                ]);

                Notification::make()->title('Jadwal diperbarui')->success()->send();
                $this->dispatch('schedule-reload');
            });
    }

    // public function editTaskAction(): Action
    // {
    //     return Action::make('editTask')
    //         ->form(function (Form $form) {
    //             return $form
    //                 ->model(\App\Models\Schedule::class)
    //                 ->schema([
    //                     Forms\Components\Select::make('nama_plant')
    //                         ->label('Nama Plant')
    //                         ->native(false)
    //                         ->required()
    //                         ->options([
    //                             'PLANT A' => 'PLANT A',
    //                             'PLANT B' => 'PLANT B',
    //                             'PLANT C' => 'PLANT C',
    //                             'PLANT D' => 'PLANT D',
    //                             'PLANT E' => 'PLANT E',
    //                         ]),

    //                     Forms\Components\Select::make('mesin_id')
    //                         ->label('Nama Mesin')
    //                         ->disabled(fn(Get $get) => empty($get('nama_plant')))
    //                         ->native(false)
    //                         ->searchable()
    //                         ->preload()
    //                         ->relationship('mesins', 'nama_mesin')
    //                         ->required()
    //                         ->afterStateUpdated(function (Set $set) {
    //                             $set('mesin_id', null);
    //                         }),

    //                     Forms\Components\DatePicker::make('rencana_cek')
    //                         ->label('Rencana Cek')
    //                         ->required()
    //                         ->native(false),

    //                     Forms\Components\Textarea::make('keterangan_note')
    //                         ->label('Keterangan')
    //                         ->columnSpanFull(),
    //                 ]);
    //         })
    //         ->modalHeading('Edit Jadwal Cek')
    //         ->mountUsing(function (Form $form, array $arguments) {
    //             $m = ModelsSchedule::find((int) ($arguments['id'] ?? 0));

    //             if ($m) {
    //                 $form->fill([
    //                     'nama_plant'      => $m->nama_plant,
    //                     'nama_mesin'      => $m->nama_mesin,
    //                     'rencana_cek'     => optional($m->rencana_cek)?->format('Y-m-d'),
    //                     'keterangan_note' => $m->keterangan_note,
    //                 ]);
    //             }
    //         })
    //         ->action(function (array $data, array $arguments) {
    //             $id = (int) ($arguments['id'] ?? 0);
    //             $m  = ModelsSchedule::find($id);

    //             if (! $m) {
    //                 Notification::make()->title('Jadwal tidak ditemukan')->danger()->send();
    //                 return;
    //             }

    //             $newDate = isset($data['rencana_cek'])
    //                 ? Carbon::parse($data['rencana_cek'])->format('Y-m-d')
    //                 : null;

    //             if ($newDate && $this->exceedsGlobalDailyLimit($newDate, $id)) {
    //                 Notification::make()->title("Gagal: Maksimal 4 mesin pada {$newDate}")->danger()->send();
    //                 return;
    //             }

    //             $m->update([
    //                 'nama_plant'      => $data['nama_plant'],
    //                 'nama_mesin'      => $data['nama_mesin'],
    //                 'rencana_cek'     => $newDate,
    //                 'keterangan_note' => $data['keterangan_note'] ?? null,
    //             ]);

    //             Notification::make()->title('Jadwal diperbarui')->success()->send();
    //             $this->dispatch('schedule-reload');
    //         });
    // }

    /**
     * Action: Delete Task (dibuka dari JS via openDeleteAction()).
     */
    public function deleteTaskAction(): Action
    {
        return Action::make('deleteTask')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $id = (int) ($arguments['id'] ?? 0);
                $m  = ModelsSchedule::find($id);

                if (! $m) {
                    Notification::make()->title('Jadwal tidak ditemukan')->danger()->send();
                    return;
                }

                // (opsional) bersihkan dependency terkait di sini
                $m->delete();

                Notification::make()->title('Jadwal dihapus')->success()->send();
                $this->dispatch('schedule-reload');
            });
    }

    /**
     * Helper untuk memanggil modal Edit dari sisi JS/Alpine.
     */
    public function openEditAction(array $payload = []): void
    {
        $this->mountAction('editTask', ['id' => (int) ($payload['id'] ?? 0)]);
    }

    /**
     * Helper untuk memanggil modal Delete dari sisi JS/Alpine.
     */
    public function openDeleteAction(array $payload = []): void
    {
        $this->mountAction('deleteTask', ['id' => (int) ($payload['id'] ?? 0)]);
    }
}
