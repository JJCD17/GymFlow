<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => config('gymflow.super_admin.email')],
            [
                'name' => config('gymflow.super_admin.name'),
                'username' => config('gymflow.super_admin.user'),
                'password' => config('gymflow.super_admin.password'),
                'role' => User::ROLE_SUPER_ADMIN,
            ],
        );

        $this->command->info("Super-admin: {$superAdmin->username}");
    }
}
