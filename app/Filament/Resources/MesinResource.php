<?php

namespace App\Filament\Resources;

use App\Filament\Imports\MesinImporter;
use App\Filament\Resources\MesinResource\Pages;
use App\Filament\Resources\MesinResource\RelationManagers;
use App\Models\Mesin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MesinResource extends Resource
{
    protected static ?string $model = Mesin::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $slug = 'mesin';
    protected static ?string $navigationLabel = 'Data Mesin';
    protected static ?string $navigationGroup = 'Settings';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Select::make('nama_plant')
                //     ->required()
                //     ->options([
                //         'PLANT A' => 'PLANT A',
                //         'PLANT B' => 'PLANT B',
                //         'PLANT C' => 'PLANT C',
                //         'PLANT D' => 'PLANT D',
                //         'PLANT E' => 'PLANT E',
                //         'PLANT SS' => 'PLANT SS',
                //     ]),
                Forms\Components\TextInput::make('nama_plant')
                    ->label('Nama Plant')
                    ->required()
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn(?string $state) => $state ? strtoupper($state) : null)
                    ->maxLength(20)
                    ->datalist(
                        Mesin::query()
                            ->distinct() //(biar tidak duplikat)
                            ->pluck('nama_plant')
                            ->toArray()
                    )
                    ->placeholder('Pilih Plant atau ketik baru...'),
                Forms\Components\TextInput::make('nama_mesin')
                    ->required()
                    ->maxLength(20),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nama_plant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_mesin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('nama_plant')
                    ->label('Filter Plant')
                    ->searchable()
                    ->options(
                        fn() => Mesin::query()
                            ->distinct()
                            ->orderBy('nama_plant')
                            ->pluck('nama_plant', 'nama_plant')
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMesins::route('/'),
        ];
    }
}
