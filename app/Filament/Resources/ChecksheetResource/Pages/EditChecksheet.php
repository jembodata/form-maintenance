<?php

namespace App\Filament\Resources\ChecksheetResource\Pages;

use App\Filament\Resources\ChecksheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChecksheet extends EditRecord
{
    protected static string $resource = ChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
