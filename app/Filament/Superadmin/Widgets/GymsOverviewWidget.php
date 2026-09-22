<?php

namespace App\Filament\Superadmin\Widgets;

use App\Filament\Superadmin\Resources\Gyms\GymResource;
use App\Models\Gym;
use Filament\Widgets\Widget;

class GymsOverviewWidget extends Widget
{
    protected string $view = 'filament.superadmin.widgets.gyms-overview';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public function getViewData(): array
    {
        $total = Gym::count();
        $activos = Gym::where('is_active', true)->count();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $total - $activos,
            'gimnasiosUrl' => GymResource::getUrl('index'),
        ];
    }
}
