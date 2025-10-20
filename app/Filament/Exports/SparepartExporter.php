<?php

namespace App\Filament\Exports;

use App\Models\Sparepart;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SparepartExporter extends Exporter
{
    protected static ?string $model = Sparepart::class;

    public static function getColumns(): array
    {
        return [
            //
            ExportColumn::make('id')
                ->label('No')
                ->enabledByDefault(false),
            ExportColumn::make('Tanggal')
                ->label('Dibuat Pada'),
            ExportColumn::make('Tipe_Maintenance')
                ->label('Tipe Maintenance'),
            ExportColumn::make('Kelompok'),
            ExportColumn::make('Item'),
            ExportColumn::make('Deskripsi')
                ->formatStateUsing(fn(string $state): string => strip_tags($state)),
            ExportColumn::make('Masuk')
                ->label('Masuk (Qty)'),
            ExportColumn::make('Keluar')
                ->label('Keluar (Qty)'),
            ExportColumn::make('Stok')
                ->label('Stok (Qty)'),
            ExportColumn::make('Nama_Plant')
                ->label('Plant'),
            ExportColumn::make('Nama_Mesin')
                ->label('Mesin'),
            ExportColumn::make('Nama_Bagian')
                ->label('Bagian'),
            ExportColumn::make('Nama_Operator')
                ->label('Operator'),
            ExportColumn::make('Cycle_Time')
                ->label('Cycle Time (s)'),
            ExportColumn::make('Tanggal_Kembali')
                ->label('Tanggal Kembali'),
            // ExportColumn::make('created_at')
            //     ->label('Dibuat Pada'),
            ExportColumn::make('updated_at')
                ->label('Diperbarui Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sparepart export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
