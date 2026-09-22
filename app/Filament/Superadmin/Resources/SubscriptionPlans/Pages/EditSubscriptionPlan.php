<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans\Pages;

use App\Filament\Superadmin\Resources\SubscriptionPlans\SubscriptionPlanResource;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use App\Models\SubscriptionPlan;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar')
                ->modalHeading(fn (SubscriptionPlan $record) => $record->isDeletable()
                    ? "¿Borrar «{$record->name}»?"
                    : "No se puede borrar «{$record->name}»")
                ->modalIcon(fn (SubscriptionPlan $record) => $record->isDeletable()
                    ? 'heroicon-o-trash'
                    : 'heroicon-o-lock-closed')
                ->modalIconColor(fn (SubscriptionPlan $record) => $record->isDeletable() ? 'danger' : 'warning')
                ->modalDescription(fn (SubscriptionPlan $record) => SubscriptionPlansTable::deleteDescription($record))
                ->modalSubmitAction(fn (SubscriptionPlan $record) => $record->isDeletable() ? null : false)
                ->modalCancelActionLabel(fn (SubscriptionPlan $record) => $record->isDeletable()
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
