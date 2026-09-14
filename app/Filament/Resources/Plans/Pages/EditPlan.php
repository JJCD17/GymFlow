<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Filament\Resources\Plans\Tables\PlansTable;
use App\Models\Plan;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->modalHeading(fn (Plan $record) => $record->isDeletable()
                    ? "¿Borrar «{$record->name}»?"
                    : "No se puede borrar «{$record->name}»")
                ->modalIcon(fn (Plan $record) => $record->isDeletable()
                    ? 'heroicon-o-trash'
                    : 'heroicon-o-lock-closed')
                ->modalIconColor(fn (Plan $record) => $record->isDeletable() ? 'danger' : 'warning')
                ->modalDescription(fn (Plan $record) => PlansTable::deleteDescription($record))
                ->modalSubmitAction(fn (Plan $record) => $record->isDeletable() ? null : false)
                ->modalCancelActionLabel(fn (Plan $record) => $record->isDeletable()
                    ? 'Cancelar'
                    : 'Entendido'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plan actualizado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
