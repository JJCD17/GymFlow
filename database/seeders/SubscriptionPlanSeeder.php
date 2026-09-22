<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Support\SubscriptionPlanTemplates;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SubscriptionPlanTemplates::all() as $plan) {
            SubscriptionPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }

        $this->command->info('Planes de suscripción: '.SubscriptionPlan::count());
    }
}
