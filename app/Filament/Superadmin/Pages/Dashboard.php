<?php

namespace App\Filament\Superadmin\Pages;

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
}
