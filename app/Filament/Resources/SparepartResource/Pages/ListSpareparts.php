<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Filament\Exports\SparepartExporter;
use App\Filament\Resources\SparepartResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Pages\ListRecords;

class ListSpareparts extends ListRecords
{
    protected static string $resource = SparepartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->slideOver()
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->createAnother(false)
                ->modalWidth(MaxWidth::FourExtraLarge),
            Actions\ExportAction::make()->exporter(SparepartExporter::class)
                ->label('Export Data')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('primary')
                ->slideOver()
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->formats([
                    ExportFormat::Xlsx,
                ]),
        ];
    }
}
