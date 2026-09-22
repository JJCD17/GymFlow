<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Models\Gym;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SesionAbiertaAlVencerTest extends TestCase
{
    use RefreshDatabase;

    protected function gymConSuscripcion(): Gym
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Mensual',
            'duration_days' => 30,
            'price' => 500,
        ]);

        return app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gym Uno'],
            ownerData: [
                'name' => 'Dueño Uno',
                'username' => 'uno',
                'email' => 'uno@test.test',
                'password' => 'secret123',
            ],
            subscriptionData: [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
            ],
        );
    }

    /**
     * La sesión se inicia de verdad para que cada request vuelva a cargar al
     * usuario desde la base, como en producción: pasarle el modelo a actingAs
     * lo dejaría vivo entre requests y escondería el problema.
     */
    protected function iniciarSesion(Gym $gym): void
    {
        Auth::loginUsingId($gym->owner->id);
    }

    public function test_la_sesion_abierta_se_corta_cuando_vence_la_suscripcion(): void
    {
        $gym = $this->gymConSuscripcion();
        $this->iniciarSesion($gym);

        $this->get('/admin')->assertSuccessful();

        Gym::whereKey($gym->id)->update(['suspended_at' => null]);
        $gym->activeSubscription()->update(['ends_at' => now()->subDay()]);

        $this->get('/admin')->assertForbidden();
    }

    public function test_la_sesion_abierta_se_corta_al_suspender_el_gimnasio(): void
    {
        $gym = $this->gymConSuscripcion();
        $this->iniciarSesion($gym);

        $this->get('/admin')->assertSuccessful();

        Gym::whereKey($gym->id)->update(['suspended_at' => now()]);

        $this->get('/admin')->assertForbidden();
    }

    public function test_al_vencer_se_explica_por_que_y_se_puede_cerrar_sesion(): void
    {
        $gym = $this->gymConSuscripcion();
        $this->iniciarSesion($gym);
        $gym->activeSubscription()->update(['ends_at' => now()->subDay()]);

        $respuesta = $this->get('/admin');

        $respuesta->assertForbidden();
        $respuesta->assertSee('Tu suscripción terminó');
        $respuesta->assertSee($gym->name);
        $respuesta->assertSee('Cerrar sesión');
        // Lo que más tranquiliza es saber que no se perdió nada.
        $respuesta->assertSee('sigue', false);
    }

    public function test_cerrar_sesion_desde_la_pantalla_lleva_al_login(): void
    {
        $gym = $this->gymConSuscripcion();
        $this->iniciarSesion($gym);
        $gym->activeSubscription()->update(['ends_at' => now()->subDay()]);

        $this->get('/admin')->assertForbidden();

        // El propio aviso no debe atrapar la petición de salida.
        $this->post('/admin/logout')->assertRedirect();

        $this->assertGuest();
    }

    public function test_al_suspender_el_mensaje_no_habla_de_vencimiento(): void
    {
        $gym = $this->gymConSuscripcion();
        $this->iniciarSesion($gym);
        Gym::whereKey($gym->id)->update(['suspended_at' => now()]);

        $respuesta = $this->get('/admin');

        $respuesta->assertForbidden();
        $respuesta->assertSee('Tu cuenta está pausada');
        $respuesta->assertDontSee('Tu suscripción terminó');
        $respuesta->assertDontSee('Venció el');
    }
}
