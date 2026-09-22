<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

class AuthenticatePanel extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = Filament::auth()->user();
        $currentPanel = Filament::getCurrentPanel();

        // Salir se permite siempre. Validar el acceso antes dejaría al dueño
        // de un gimnasio fuera atrapado en el aviso, sin manera de cerrar su
        // sesión ni de entrar con otra cuenta.
        if ($request->is('*/logout')) {
            return;
        }

        if ($user && $currentPanel) {
            $expected = Filament::getPanel($user->isSuperAdmin() ? 'superadmin' : 'admin');

            if ($currentPanel->getId() !== $expected->getId()) {
                redirect($expected->getUrl())->throwResponse();
            }

            // Un 403 pelón haría pensar que el sistema se descompuso. Si el
            // gimnasio quedó fuera se explica por qué, antes de que Filament
            // corte la petición. Cerrar sesión se deja pasar: si no, la
            // pantalla se devolvería a sí misma y el botón no llevaría a ningún
            // lado.
            $gym = $user->gym()->first();

            if ($gym && ! $gym->is_active) {
                $this->explainLockout($gym)->throwResponse();
            }
        }

        parent::authenticate($request, $guards);
    }

    protected function explainLockout(Gym $gym): \Illuminate\Http\Response
    {
        $suspendido = $gym->isSuspended();
        $subscription = $gym->latestSubscription();

        return response()->view('gym-sin-acceso', [
            'titulo' => $suspendido
                ? 'Tu cuenta está pausada'
                : 'Tu suscripción terminó',
            'mensajes' => $suspendido
                ? [
                    "El acceso de {$gym->name} está pausado por ahora.",
                    'Tu información sigue completa y te espera. Escríbenos para reactivarlo.',
                ]
                : [
                    "La suscripción de {$gym->name} llegó a su fin, así que el panel quedó en pausa.",
                    'Nada se perdió: tus clientes, pagos y asistencias siguen guardados. En cuanto se renueve, todo vuelve tal como lo dejaste.',
                ],
            'vencioEl' => ! $suspendido && $subscription
                ? $subscription->ends_at->translatedFormat('d \d\e F \d\e Y')
                : null,
            'salirUrl' => Filament::getPanel('admin')->getLogoutUrl(),
        ], 403);
    }
}
