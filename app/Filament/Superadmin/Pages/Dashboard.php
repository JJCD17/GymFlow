<?php

namespace App\Filament\Superadmin\Pages;

use App\Filament\Superadmin\Widgets\GymActivityWidget;
use App\Filament\Superadmin\Widgets\GymsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Inicio';

    protected static ?string $navigationLabel = 'Inicio';

    public function getHeading(): string
    {
        return 'Panel de administración';
    }

    public function getSubheading(): ?string
    {
        return 'Gimnasios registrados en GymFlow.';
    }

    public function getWidgets(): array
    {
        return [
            GymsOverviewWidget::class,
            GymActivityWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
