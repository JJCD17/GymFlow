<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans\Pages;

use App\Filament\Superadmin\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPlan extends CreateRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plan de suscripción creado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
