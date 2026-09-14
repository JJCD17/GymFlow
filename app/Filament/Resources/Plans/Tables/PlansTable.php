<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('MXN')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Duración')
                    ->formatStateUsing(fn (int $state) => $state === 1 ? '1 día' : "{$state} días")
                    ->sortable(),
                TextColumn::make('memberships_count')
                    ->label('Vendidas')
                    ->counts('memberships')
                    ->alignCenter()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
                    ->label(fn (Plan $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (Plan $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Plan $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Plan $record) => $record->is_active
                        ? 'Dejará de ofrecerse al cobrar. Las membresías ya vendidas siguen igual.'
                        : 'Volverá a aparecer en la lista al registrar y renovar clientes.')
                    ->action(fn (Plan $record) => $record->update(['is_active' => ! $record->is_active])),
                EditAction::make()->label('Editar'),
                DeleteAction::make()
                    ->label('Borrar')
                    // Borrar un plan ya vendido dejaría sin referencia el
                    // historial de esos clientes. El botón sigue visible para
                    // poder explicar el motivo y ofrecer desactivarlo.
                    ->modalHeading(fn (Plan $record) => $record->isDeletable()
                        ? "¿Borrar «{$record->name}»?"
                        : "No se puede borrar «{$record->name}»")
                    ->modalIcon(fn (Plan $record) => $record->isDeletable()
                        ? 'heroicon-o-trash'
                        : 'heroicon-o-lock-closed')
                    ->modalIconColor(fn (Plan $record) => $record->isDeletable() ? 'danger' : 'warning')
                    ->modalDescription(fn (Plan $record) => self::deleteDescription($record))
                    ->modalSubmitAction(fn (Plan $record) => $record->isDeletable() ? null : false)
                    ->modalCancelActionLabel(fn (Plan $record) => $record->isDeletable()
                        ? 'Cancelar'
                        : 'Entendido')
                    ->extraModalFooterActions(fn (Plan $record) => ! $record->isDeletable() && $record->is_active
                        ? [
                            Action::make('deactivateInstead')
                                ->label('Desactivar')
                                ->icon('heroicon-o-pause-circle')
                                ->color('warning')
                                ->cancelParentActions()
                                ->action(fn () => self::deactivate($record)),
                        ]
                        : []),
            ])
            // El orden decide cómo se listan los planes al cobrar.
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering) => $action
                ->button()
                ->label($isReordering ? 'Listo' : 'Cambiar orden')
                ->icon($isReordering ? 'heroicon-o-check' : 'heroicon-o-arrows-up-down')
                ->color($isReordering ? 'primary' : 'gray')
                ->tooltip($isReordering
                    ? null
                    : 'Acomoda los planes como quieres verlos al cobrar.'))
            ->defaultSort('sort_order')
            ->emptyStateHeading('Aún no hay planes')
            ->emptyStateDescription('Crea el primero para poder cobrar membresías.');
    }

    public static function deleteDescription(Plan $record): string
    {
        if ($record->isDeletable()) {
            return 'Este plan nunca se ha vendido, así que no afecta a ningún cliente. Esta acción no se puede deshacer.';
        }

        $vendidas = $record->memberships()->count();

        $membresias = $vendidas === 1
            ? 'una membresía vendida'
            : "{$vendidas} membresías vendidas";

        $aviso = "Este plan tiene {$membresias} y es la referencia de ese historial: borrarlo dejaría esos registros sin el plan que los originó.";

        return $record->is_active
            ? "{$aviso} Si ya no quieres ofrecerlo, desactívalo: deja de aparecer al cobrar y el historial se conserva."
            : "{$aviso} Ya está desactivado, así que no aparece al cobrar.";
    }

    protected static function deactivate(Plan $record): void
    {
        $record->update(['is_active' => false]);

        Notification::make()
            ->title("«{$record->name}» quedó desactivado")
            ->body('Ya no aparece al registrar ni renovar clientes.')
            ->success()
            ->send();
    }
}
