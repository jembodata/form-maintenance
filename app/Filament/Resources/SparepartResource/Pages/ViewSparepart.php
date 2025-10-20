<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Filament\Resources\SparepartResource;
use App\Filament\Resources\SparepartResource\RelationManagers\StockHistoriesRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSparepart extends ViewRecord
{
    protected static string $resource = SparepartResource::class;
}
