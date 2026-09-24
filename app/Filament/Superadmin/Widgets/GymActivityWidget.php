<?php

namespace App\Filament\Superadmin\Widgets;

use App\Filament\Superadmin\Resources\Gyms\GymResource;
use App\Support\GymActivity;
use Filament\Widgets\Widget;

class GymActivityWidget extends Widget
{
    protected string $view = 'filament.superadmin.widgets.gym-activity';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getViewData(): array
    {
        $gyms = GymActivity::forActiveGyms();
        $porEstado = $gyms->countBy('status');

        return [
            'total' => $gyms->count(),
            'alDia' => $porEstado[GymActivity::STATUS_OK] ?? 0,
            'sinUso' => $porEstado[GymActivity::STATUS_IDLE] ?? 0,
            'sinEstrenar' => $porEstado[GymActivity::STATUS_UNSTARTED] ?? 0,
            'pocoUso' => $porEstado[GymActivity::STATUS_LOW_USE] ?? 0,
            'atencion' => $gyms
                ->reject(fn (array $row) => $row['status'] === GymActivity::STATUS_OK)
                ->map(fn (array $row) => [
                    ...$row,
                    'url' => GymResource::getUrl('edit', ['record' => $row['gym']]),
                ]),
            'diasSinUso' => GymActivity::IDLE_DAYS,
        ];
    }
}
