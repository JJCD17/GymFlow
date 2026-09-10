<?php

namespace App\Filament\Resources\Members\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CheckInsRelationManager extends RelationManager
{
    protected static string $relationship = 'checkIns';

    protected static ?string $title = 'Asistencias';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('checked_in_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn ($record) => $record->checked_in_at->diffForHumans()),
            ])
            ->defaultSort('checked_in_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateHeading('Sin asistencias registradas');
    }
}
