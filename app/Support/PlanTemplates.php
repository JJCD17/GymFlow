<?php

namespace App\Support;

class PlanTemplates
{
    public static function all(): array
    {
        return [
            ['name' => 'Visita por día', 'duration_days' => 1, 'price' => 50, 'sort_order' => 1],
            ['name' => 'Mensual', 'duration_days' => 30, 'price' => 400, 'sort_order' => 2],
            ['name' => 'Trimestral', 'duration_days' => 90, 'price' => 1100, 'sort_order' => 3],
            ['name' => 'Semestral', 'duration_days' => 180, 'price' => 2000, 'sort_order' => 4],
            ['name' => 'Anual', 'duration_days' => 365, 'price' => 3600, 'sort_order' => 5],
        ];
    }
}
