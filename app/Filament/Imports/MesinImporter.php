<?php

namespace App\Filament\Imports;

use App\Models\Mesin;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class MesinImporter extends Importer
{
    protected static ?string $model = Mesin::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_plant')
                ->requiredMapping()
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:255']),
            ImportColumn::make('nama_mesin')
                ->requiredMapping()
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Mesin
    {
        // return Mesin::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Mesin();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your mesin import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
