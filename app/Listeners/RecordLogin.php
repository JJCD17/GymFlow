<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;
        $user->timestamps = false;
        $user->forceFill(['last_login_at' => now(), 'last_seen_at' => now()])->saveQuietly();
        $user->timestamps = true;
    }
}
