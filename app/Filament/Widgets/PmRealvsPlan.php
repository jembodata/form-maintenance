<?php

namespace App\Filament\Widgets;

use App\Models\Checksheet;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PmRealvsPlan extends ChartWidget
{
    protected static ?string $heading = 'Preventive Mesin';
    protected static ?string $description = 'Perbandingan Realisasi vs Rencana';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $year = now()->year;

        $realisasi = Checksheet::query()
            ->selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->whereYear('date', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->all();

        $rencana = Schedule::query()
            ->selectRaw('MONTH(rencana_cek) as month, COUNT(*) as total')
            ->whereYear('rencana_cek', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->all();

        $realisasiData = [];
        $rencanaData = [];
        $labels = [];

        for ($m = 1; $m <= 12; $m++) {
            $realisasiData[] = $realisasi[$m] ?? 0;
            $rencanaData[] = $rencana[$m] ?? 0;
            $labels[] = Carbon::create()->month($m)->shortMonthName;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Realisasi',
                    'data' => $realisasiData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgb(75, 192, 192)',
                ],
                [
                    'label' => 'Rencana',
                    'data' => $rencanaData,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgb(255, 159, 64)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
