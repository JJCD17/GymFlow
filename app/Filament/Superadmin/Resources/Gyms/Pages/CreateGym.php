<?php

namespace App\Filament\Superadmin\Resources\Gyms\Pages;

use App\Actions\CreateGymWithOwner;
use App\Filament\Superadmin\Resources\Gyms\GymResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGym extends CreateRecord
{
    protected static string $resource = GymResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateGymWithOwner::class)->handle(
            gymData: [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'timezone' => $data['timezone'],
            ],
            ownerData: [
                'name' => $data['owner_name'],
                'username' => $data['owner_username'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'],
            ],
            subscriptionData: [
                'subscription_plan_id' => $data['subscription_plan_id'],
                'starts_at' => $data['subscription_starts_at'],
            ],
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Gimnasio creado con su dueño, suscripción y planes iniciales';
    }
}
