<?php

namespace App\Actions;

use App\Models\Gym;
use App\Models\User;
use App\Support\PlanTemplates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateGymWithOwner
{
    public function handle(array $gymData, array $ownerData): Gym
    {
        return DB::transaction(function () use ($gymData, $ownerData) {
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
