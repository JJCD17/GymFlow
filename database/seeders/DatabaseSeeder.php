<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            $this->command->error('Estos seeders crean datos de ejemplo y solo corren en entorno local.');

            return;
        }

        $this->call([
            SuperAdminSeeder::class,
            GymSeeder::class,
            MemberSeeder::class,
        ]);
    }
}
