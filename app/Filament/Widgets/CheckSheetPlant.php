<?php

namespace App\Filament\Widgets;

use App\Models\Checksheet;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend; // gadipake
use Flowframe\Trend\TrendValue;  // gadipake
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

// Bar Chart
// class CheckSheetPlant extends ChartWidget
// {
//     use HasWidgetShield;

//     protected static ?string $heading = 'PM by Plant';

//     protected function getType(): string
//     {
//         return 'bar';
//     }

//     protected function getOptions(): array
//     {
//         return [
//             'plugins' => [
//                 'title' => [
//                     'display' => true,
//                 ],
//             ],
//             'responsive' => true,
//             'scales' => [
//                 'x' => ['stacked' => true],
//                 'y' => ['stacked' => true],
//             ],
//         ];
//     }

//     protected function getData(): array
//     {
//         $currentYear = now()->year;

//         $rows = Checksheet::query()
//             ->select(
//                 DB::raw('MONTH(created_at) as month'),
//                 'plant_area',
//                 DB::raw('count(*) as total')
//             )
//             ->whereYear('created_at', $currentYear)
//             ->groupBy('month', 'plant_area')
//             ->orderBy('month')
//             ->get();

//         $months = range(1, 12);
//         $labels = array_map(fn($m) => Carbon::create()->month($m)->format('M'), $months);

//         $plants = $rows->groupBy('plant_area');

//         // Static color (tidak berubah-ubah)
//         $colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#06b6d4'];
//         $datasets = [];
//         $colorIndex = 0;

//         foreach ($plants as $plant => $groupedData) {
//             $monthlyData = array_fill(1, 12, 0);

//             foreach ($groupedData as $item) {
//                 $monthlyData[(int) $item->month] = $item->total;
//             }

//             $datasets[] = [
//                 'label' => $plant,
//                 'data' => array_values($monthlyData),
//                 'backgroundColor' => $colors[$colorIndex] ?? '#ccc', // fallback abu-abu
//                 'borderColor' => 'transparent',
//             ];

//             $colorIndex++;
//         }

//         return [
//             'labels' => $labels,
//             'datasets' => $datasets,
//         ];
//     }
// }

// Doughnat Chart
class CheckSheetPlant extends ChartWidget 
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?string $heading = 'PM by Plant';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false]
            ],
        ];
    }

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Per Tahun',
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];
    }

    protected function getData(): array
    {
        $currentYear = now()->year;
        $selectedMonth = $this->filter ?? now()->format('m');

        // Query dasar
        $query = Checksheet::query()
            ->select('plant_area', DB::raw('COUNT(*) as total'))
            ->whereYear('created_at', $currentYear);

        // Filter bulan jika bukan "all"
        if ($selectedMonth !== 'all') {
            $query->whereMonth('created_at', $selectedMonth);
        }

        // Eksekusi dan ambil data
        $data = $query->groupBy('plant_area')
            ->orderBy('plant_area')
            ->get();

        $colors = [
            '#ef4444', // Red
            '#f97316', // Orange
            '#f59e0b', // Amber
            '#84cc16', // Lime
            '#06b6d4', // Cyan
            '#3b82f6', // Blue
            '#8b5cf6', // Violet
            '#ec4899', // Pink
        ];

        return [
            'labels' => $data->pluck('plant_area')->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

}