<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Members\MemberResource;
use App\Support\GymPulse;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TodayPulseWidget extends Widget
{
    protected string $view = 'filament.widgets.today-pulse';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public function getViewData(): array
    {
        $pulse = GymPulse::for(Auth::user()->gym);

        $hoy = $pulse->peopleToday();
        $anterior = $pulse->peopleOnPreviousOpenDay();
        $dias = $pulse->recentDays();

        return [
            'cerradoHoy' => $pulse->isClosedToday(),
            'hoy' => $hoy,
            'anterior' => $anterior,
            'diaAnterior' => $pulse->previousOpenDay(),
            'diferencia' => $hoy - $anterior,
            'alCorriente' => $pulse->activeMembers(),
            'porVenir' => $pulse->pendingToday(),
            'ausentes' => $pulse->absentMembers(),
            'dias' => $dias,
            'maxDia' => max(1, (int) $dias->max('personas')),
            'visitantes' => $pulse->todaysVisitors(),
            'clientesUrl' => MemberResource::getUrl('index'),
        ];
    }
}
