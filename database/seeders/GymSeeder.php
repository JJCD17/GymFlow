<?php

namespace Database\Seeders;

use App\Actions\CreateGymWithOwner;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::active()->orderBy('sort_order')->firstOrFail();

        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gimnasio Demo', 'phone' => '6141234567'],
            ownerData: [
                'name' => 'Dueño Demo',
                'username' => 'demo',
                'email' => 'dueno@demo.test',
                'password' => config('gymflow.super_admin.password'),
            ],
            subscriptionData: [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
            ],
        );

        $this->command->info("Gimnasio: {$gym->name} · dueno@demo.test · {$gym->plans()->count()} planes · suscripción {$plan->name}");
    }
}
