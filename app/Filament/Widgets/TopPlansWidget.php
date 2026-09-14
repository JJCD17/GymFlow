<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Plans\PlanResource;
use App\Support\PlanSales;
use Filament\Widgets\Widget;

class TopPlansWidget extends Widget
{
    protected string $view = 'filament.widgets.top-plans';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public string $range = '30';

    /**
     * @var array<string, string>
     */
    public array $ranges = PlanSales::RANGES;

    public function getViewData(): array
    {
        $filas = PlanSales::forRange($this->range);

        return [
            'filas' => $filas,
            'ventasTotales' => $filas->sum('ventas'),
            'ingresosTotales' => $filas->sum('ingresos'),
            'maxVentas' => (int) $filas->max('ventas'),
            'planesUrl' => PlanResource::getUrl('index'),
        ];
    }
}
