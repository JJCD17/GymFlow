<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans\Tables;

use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->description(fn (SubscriptionPlan $record) => $record->is_trial ? 'periodo de prueba' : null)
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
                TextColumn::make('subscriptions_count')
                    ->label('Contratado')
                    ->counts('subscriptions')
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
                    ->label(fn (SubscriptionPlan $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (SubscriptionPlan $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (SubscriptionPlan $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (SubscriptionPlan $record) => $record->is_active
                        ? 'Dejará de ofrecerse al contratar. Las suscripciones vigentes siguen igual.'
                        : 'Volverá a aparecer al dar de alta y renovar gimnasios.')
                    ->action(fn (SubscriptionPlan $record) => $record->update(['is_active' => ! $record->is_active])),
                EditAction::make()->label('Editar'),
                DeleteAction::make()
                    ->label('Borrar')
                    // Borrar un plan ya contratado dejaría sin referencia el
                    // historial de esos gimnasios. El botón sigue visible para
                    // poder explicar el motivo y ofrecer desactivarlo.
                    ->modalHeading(fn (SubscriptionPlan $record) => $record->isDeletable()
                        ? "¿Borrar «{$record->name}»?"
                        : "No se puede borrar «{$record->name}»")
                    ->modalIcon(fn (SubscriptionPlan $record) => $record->isDeletable()
                        ? 'heroicon-o-trash'
                        : 'heroicon-o-lock-closed')
                    ->modalIconColor(fn (SubscriptionPlan $record) => $record->isDeletable() ? 'danger' : 'warning')
                    ->modalDescription(fn (SubscriptionPlan $record) => self::deleteDescription($record))
                    ->modalSubmitAction(fn (SubscriptionPlan $record) => $record->isDeletable() ? null : false)
                    ->modalCancelActionLabel(fn (SubscriptionPlan $record) => $record->isDeletable()
                        ? 'Cancelar'
                        : 'Entendido')
                    ->extraModalFooterActions(fn (SubscriptionPlan $record) => ! $record->isDeletable() && $record->is_active
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
            ->reorderable('sort_order')
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering) => $action
                ->button()
                ->label($isReordering ? 'Listo' : 'Cambiar orden')
                ->icon($isReordering ? 'heroicon-o-check' : 'heroicon-o-arrows-up-down')
                ->color($isReordering ? 'primary' : 'gray')
                ->tooltip($isReordering
                    ? null
                    : 'Acomoda los planes como quieres verlos al contratar.'))
            ->defaultSort('sort_order')
            ->emptyStateHeading('Aún no hay planes de suscripción')
            ->emptyStateDescription('Crea el primero para poder dar de alta gimnasios.');
    }

    public static function deleteDescription(SubscriptionPlan $record): string
    {
        if ($record->isDeletable()) {
            return 'Este plan nunca se ha contratado, así que no afecta a ningún gimnasio. Esta acción no se puede deshacer.';
        }

        $contratadas = $record->subscriptions()->count();

        $suscripciones = $contratadas === 1
            ? 'una suscripción'
            : "{$contratadas} suscripciones";

        $aviso = "Este plan tiene {$suscripciones} y es la referencia de ese historial: borrarlo dejaría esos registros sin el plan que los originó.";

        return $record->is_active
            ? "{$aviso} Si ya no quieres ofrecerlo, desactívalo: deja de aparecer al contratar y el historial se conserva."
            : "{$aviso} Ya está desactivado, así que no aparece al contratar.";
    }

    protected static function deactivate(SubscriptionPlan $record): void
    {
        $record->update(['is_active' => false]);

        Notification::make()
            ->title("«{$record->name}» quedó desactivado")
            ->body('Ya no aparece al dar de alta ni renovar gimnasios.')
            ->success()
            ->send();
    }
}
