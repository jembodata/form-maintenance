<?php

namespace App\Filament\Resources\MesinResource\Pages;

use App\Filament\Imports\MesinImporter;
use App\Filament\Resources\MesinResource;
use Filament\Actions;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Pages\ManageRecords;

class ManageMesins extends ManageRecords
{
    protected static string $resource = MesinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->slideOver()
                ->closeModalByEscaping(false)
                ->closeModalByClickingAway(false)
                ->modalWidth(MaxWidth::Medium),
            Actions\ImportAction::make()
                ->label('Import from Excel')
                ->color('info')
                ->slideOver()
                ->modalWidth(MaxWidth::Medium)
                ->icon('heroicon-o-inbox-arrow-down')
                ->importer(MesinImporter::class),
        ];
    }
}
