<?php

namespace App\Filament\Widgets;

use App\Models\Sparepart;
use Filament\Widgets\Widget;

class StokOverview extends Widget
{
    protected static string $view = 'filament.widgets.stok-overview';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected function getViewData(): array
    {
        $row = Sparepart::query()
            ->selectRaw("
                SUM(CASE WHEN `Stok` = 0 THEN 1 ELSE 0 END) AS kosong,
                SUM(CASE WHEN `Stok` BETWEEN 1 AND 5 THEN 1 ELSE 0 END) AS hampir,
                SUM(CASE WHEN `Stok` BETWEEN 6 AND 20 THEN 1 ELSE 0 END) AS menipis,
                SUM(CASE WHEN `Stok` > 20 THEN 1 ELSE 0 END) AS aman,
                COUNT(*) AS total
            ")
            ->first();

        return [
            'kosong' => (int) $row->kosong,
            'hampir' => (int) $row->hampir,
            'menipis' => (int) $row->menipis,
            'aman'   => (int) $row->aman,
            'total'  => (int) $row->total,
        ];
    }
}
