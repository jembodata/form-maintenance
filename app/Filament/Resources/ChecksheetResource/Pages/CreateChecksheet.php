<?php

namespace App\Filament\Resources\ChecksheetResource\Pages;

use App\Filament\Resources\ChecksheetResource;
use App\Models\Checksheet;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateChecksheet extends CreateRecord
{
    protected static string $resource = ChecksheetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('Submit'))
            ->submit('create')
            ->closeModalByEscaping(false)
            ->closeModalByClickingAway(false)
            ->slideOver();
    }
    


    // protected function getFormActions(): array
    // {
    //     return [
    //         $this->getCreateFormAction(),
    //         ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label("Test")] : []),
    //         $this->getCancelFormAction(),
    //     ];
    // }
}
