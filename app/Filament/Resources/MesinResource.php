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
                Forms\Components\Select::make('nama_plant')
                    ->required()
                    ->options([
                        'PLANT A' => 'PLANT A',
                        'PLANT B' => 'PLANT B',
                        'PLANT C' => 'PLANT C',
                        'PLANT D' => 'PLANT D',
                        'PLANT E' => 'PLANT E',
                    ]),
                Forms\Components\TextInput::make('nama_mesin')
                    ->required()
                    ->maxLength(20),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            ->filters([
                //
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
