<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

class AuthenticatePanel extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = Filament::auth()->user();
        $currentPanel = Filament::getCurrentPanel();

        if ($user && $currentPanel) {
            $expected = Filament::getPanel($user->isSuperAdmin() ? 'superadmin' : 'admin');

            if ($currentPanel->getId() !== $expected->getId()) {
                redirect($expected->getUrl())->throwResponse();
            }
        }

        parent::authenticate($request, $guards);
    }
}
