<?php

namespace App\Filament\Superadmin\Resources\Gyms\Tables;

use App\Models\Gym;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GymsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('owner'))
            ->columns([
                TextColumn::make('name')
                    ->label('Gimnasio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Dueño')
                    ->description(fn (Gym $record) => $record->owner?->username
                        ? "usuario: {$record->owner->username}"
                        : $record->owner?->email)
                    ->searchable()
                    ->placeholder('Sin dueño'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('members_count')
                    ->label('Clientes')
                    ->counts('members')
                    ->alignCenter()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn (Gym $record) => $record->is_active ? 'Suspender' : 'Reactivar')
                    ->icon(fn (Gym $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Gym $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Gym $record) => $record->is_active
                        ? 'Sus usuarios no podrán entrar hasta reactivarlo. No se borra ninguna información.'
                        : 'Sus usuarios podrán volver a entrar al sistema.')
                    ->action(fn (Gym $record) => $record->update(['is_active' => ! $record->is_active])),
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Aún no hay gimnasios')
            ->emptyStateDescription('Da de alta el primero para empezar.');
    }
}
