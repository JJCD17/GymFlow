<?php

namespace App\Support;

class SubscriptionPlanTemplates
{
    public static function all(): array
    {
        return [
            ['name' => 'Mes de prueba', 'duration_days' => 30, 'price' => 0, 'is_trial' => true, 'sort_order' => 1],
            ['name' => 'Semanal', 'duration_days' => 7, 'price' => 150, 'sort_order' => 2],
            ['name' => 'Mensual', 'duration_days' => 30, 'price' => 500, 'sort_order' => 3],
            ['name' => 'Semestral', 'duration_days' => 180, 'price' => 2700, 'sort_order' => 4],
            ['name' => 'Anual', 'duration_days' => 365, 'price' => 5000, 'sort_order' => 5],
        ];
    }
}
