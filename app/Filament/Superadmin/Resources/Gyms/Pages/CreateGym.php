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
                'is_active' => $data['is_active'] ?? true,
            ],
            ownerData: [
                'name' => $data['owner_name'],
                'username' => $data['owner_username'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'],
            ],
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Gimnasio creado con su dueño y planes iniciales';
    }
}
