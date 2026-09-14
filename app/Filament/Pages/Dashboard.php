<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TopPlansWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Escritorio';

    protected static ?string $navigationLabel = 'Escritorio';

    public function getHeading(): string
    {
        $user = Auth::user();

        return "Bienvenido {$user->name}";
    }

    public function getSubheading(): ?string
    {
        $user = Auth::user();

        return 'Panel de '.$user->gym->name;
    }

    public function getWidgets(): array
    {
        return [
            TopPlansWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
