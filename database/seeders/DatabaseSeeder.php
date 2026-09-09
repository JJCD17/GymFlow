<?php

namespace Database\Seeders;

use App\Actions\CreateGymWithOwner;
use App\Actions\RegisterMembership;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command->error('Este seeder crea datos de ejemplo y solo corre en entorno local.');

            return;
        }

        $superAdmin = User::firstOrCreate(
            ['email' => config('gymflow.super_admin.email')],
            [
                'name' => config('gymflow.super_admin.name'),
                'password' => config('gymflow.super_admin.password'),
                'role' => User::ROLE_SUPER_ADMIN,
            ],
        );

        $this->command->info("Super-admin: {$superAdmin->email}");

        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gimnasio Demo', 'phone' => '6141234567'],
            ownerData: [
                'name' => 'Dueño Demo',
                'email' => 'dueno@demo.test',
                'password' => config('gymflow.super_admin.password'),
            ],
        );

        $this->command->info("Gimnasio: {$gym->name} · dueno@demo.test");

        $this->seedMembers($gym);
    }

    protected function seedMembers(Gym $gym): void
    {
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

        $this->command->info('Clientes de ejemplo: al corriente, por vencer y vencido.');
    }
}
