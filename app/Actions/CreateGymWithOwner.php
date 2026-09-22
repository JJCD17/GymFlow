<?php

namespace App\Actions;

use App\Models\Gym;
use App\Models\User;
use App\Support\PlanTemplates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateGymWithOwner
{
    public function handle(array $gymData, array $ownerData, ?array $subscriptionData = null): Gym
    {
        return DB::transaction(function () use ($gymData, $ownerData, $subscriptionData) {
            $gym = Gym::create([
                ...$gymData,
                'code' => $this->uniqueCode($gymData['name']),
            ]);

            User::create([
                ...$ownerData,
                'gym_id' => $gym->id,
                'role' => User::ROLE_OWNER,
            ]);

            $gym->plans()->createMany(PlanTemplates::all());

            // Sin suscripción el gimnasio nacería vencido y su dueño no podría
            // entrar, así que la primera se registra en el alta.
            if ($subscriptionData) {
                $gym->subscriptions()->create($subscriptionData);
            }

            return $gym;
        });
    }

    protected function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        $code = $base;
        $suffix = 2;

        while (Gym::where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
