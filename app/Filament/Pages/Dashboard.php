<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    // public function getColumns(): int | string | array
    // {
    //     return 2;
    // }

    // public function filtersForm(Form $form): Form
    // {
    //     return $form->schema([
    //         Section::make('')->schema([
    //             DatePicker::make('created_at')
    //                 ->label('Start Date')
    //                 ->placeholder('Select a date'),
    //             DatePicker::make('created_at')
    //                 ->label('End Date')
    //                 ->placeholder('Select a date'),
    //         ])->columns(2)
    //     ]);
    // }
}
