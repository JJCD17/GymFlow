<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Models\Membership;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $title = 'Membresías';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan'),
                TextColumn::make('starts_at')
                    ->label('Inicia')
                    ->date('d/m/Y'),
                TextColumn::make('ends_at')
                    ->label('Vence')
                    ->date('d/m/Y'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Membership $record) => match (true) {
                        $record->ends_at->isPast() => 'Terminada',
                        $record->starts_at->isFuture() => 'Programada',
                        default => 'Vigente',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Vigente' => 'success',
                        'Programada' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('ends_at', 'desc')
            ->emptyStateHeading('Sin membresías registradas');
    }
}
