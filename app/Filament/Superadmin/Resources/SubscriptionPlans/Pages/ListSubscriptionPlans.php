<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans\Pages;

use App\Filament\Superadmin\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo plan'),
        ];
    }
}
