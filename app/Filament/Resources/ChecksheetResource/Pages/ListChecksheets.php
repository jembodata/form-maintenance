<?php

namespace App\Filament\Resources\ChecksheetResource\Pages;

use App\Filament\Resources\ChecksheetResource;
use App\Models\Checksheet;
use Filament\Actions;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListChecksheets extends ListRecords
{
    protected static string $resource = ChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('Create PM')
                ->slideOver()
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->createAnother(false)
                ->modalWidth(MaxWidth::FourExtraLarge),
        ];
    }

    public function getTabs(): array
    {

        $tabs = [];

        $tabs[] = Tab::make('All Checksheets')
            // Add badge to the tab
            ->badge(Checksheet::count());
        // No need to modify the query as we want to show all tasks

        $tabs[] = Tab::make('Drawing')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'drawing')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'drawing');
            });

        $tabs[] = Tab::make('Stranding')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'stranding')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'stranding');
            });

        $tabs[] = Tab::make('Cabling')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'cabling')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'cabling');
            });

        $tabs[] = Tab::make('Extruder')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'extruder')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'extruder');
            });

        $tabs[] = Tab::make('Bunching')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'bunching')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'bunching');
            });

        $tabs[] = Tab::make('Tapping')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'tapping')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'tapping');
            });

        $tabs[] = Tab::make('Tinning')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'tinning')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'tinning');
            });

        $tabs[] = Tab::make('Coloring')
            // Add badge to the tab
            ->badge(Checksheet::where('tipe_proses', 'coloring')->count())
            ->modifyQueryUsing(function ($query) {
                return $query->where('tipe_proses', 'coloring');
            });

        return $tabs;
    }
}
