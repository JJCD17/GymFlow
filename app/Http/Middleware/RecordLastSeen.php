<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordLastSeen
{
    /** Escribir en cada request sería una consulta de más por clic. */
    public const EVERY_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinutes(self::EVERY_MINUTES)))) {
            $user->timestamps = false;
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            $user->timestamps = true;
        }

        return $next($request);
    }
}
