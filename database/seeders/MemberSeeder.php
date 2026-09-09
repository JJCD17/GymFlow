<?php

namespace Database\Seeders;

use App\Actions\RegisterMembership;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $gym = Gym::firstOrFail();

        // El global scope filtra por el gimnasio del usuario autenticado, y en
        // consola no hay ninguno: sin esto, los clientes quedarían sin gimnasio.
        Auth::login($gym->owner);

        $plan = Plan::where('name', 'Mensual')->firstOrFail();
        $register = app(RegisterMembership::class);

        $examples = [
            ['Ana López', '6141112233', 0],
            ['Beto Ruiz', '6142223344', 26],
            ['Carla Díaz', '6143334455', 40],
        ];

        foreach ($examples as [$name, $phone, $daysAgo]) {
            $member = Member::create(['full_name' => $name, 'phone' => $phone]);

            $register->handle(
                member: $member,
                plan: $plan,
                amount: $plan->price,
                method: 'cash',
                startsAt: now()->subDays($daysAgo)->toDateString(),
            );
        }

        Member::where('full_name', 'Ana López')->first()
            ->checkIns()->create(['checked_in_at' => now()]);

        Auth::logout();

        $this->command->info('Clientes: al corriente, por vencer y vencido.');
    }
}
