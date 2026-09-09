<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectToRolePanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $expectedPanel = $user->isSuperAdmin() ? 'superadmin' : 'admin';

        if (Filament::getCurrentPanel()?->getId() !== $expectedPanel) {
            return redirect(Filament::getPanel($expectedPanel)->getUrl());
        }

        return $next($request);
    }
}
