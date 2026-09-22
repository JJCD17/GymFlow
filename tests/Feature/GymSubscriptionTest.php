<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Filament\Superadmin\Resources\Gyms\Pages\CreateGym;
use App\Filament\Superadmin\Resources\Gyms\Pages\ListGyms;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Models\Gym;
use App\Models\GymSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GymSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function plan(int $days = 30, string $name = 'Mensual'): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => $name,
            'duration_days' => $days,
            'price' => 500,
        ]);
    }

    protected function gymConSuscripcion(int $days = 30): Gym
    {
        $plan = $this->plan($days);

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

    protected function superAdmin(): User
    {
        $admin = User::create([
            'name' => 'Super',
            'username' => 'super',
            'email' => 'super@test.test',
            'password' => 'secret123',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($admin);

        // Las pantallas viven en el panel de superadmin; sin esto Filament
        // resuelve las rutas contra el panel del dueño y no las encuentra.
        Filament::setCurrentPanel('superadmin');

        return $admin;
    }

    public function test_la_fecha_de_vencimiento_sale_de_la_duracion_del_plan(): void
    {
        $gym = $this->gymConSuscripcion(days: 180);

        $subscription = $gym->activeSubscription();

        $this->assertSame(
            now()->addDays(180)->toDateString(),
            $subscription->ends_at->toDateString(),
        );
    }

    public function test_un_gym_con_suscripcion_vigente_esta_activo(): void
    {
        $gym = $this->gymConSuscripcion();

        $this->assertTrue($gym->is_active);
        $this->assertTrue($gym->hasActiveSubscription());
        $this->assertFalse($gym->isSuspended());
    }

    public function test_un_gym_con_suscripcion_vencida_queda_fuera(): void
    {
        $gym = $this->gymConSuscripcion();
        $gym->activeSubscription()->update(['ends_at' => now()->subDay()]);

        $gym->refresh();

        $this->assertFalse($gym->is_active);
        $this->assertFalse($gym->owner->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_un_gym_suspendido_a_mano_queda_fuera_aunque_haya_pagado(): void
    {
        $gym = $this->gymConSuscripcion();
        $gym->update(['suspended_at' => now()]);

        $gym->refresh();

        $this->assertTrue($gym->hasActiveSubscription());
        $this->assertFalse($gym->is_active);
        $this->assertFalse($gym->owner->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_reactivar_devuelve_el_acceso_si_la_suscripcion_sigue_vigente(): void
    {
        $gym = $this->gymConSuscripcion();
        $gym->update(['suspended_at' => now()]);
        $gym->update(['suspended_at' => null]);

        $gym->refresh();

        $this->assertTrue($gym->is_active);
        $this->assertTrue($gym->owner->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_una_suscripcion_cancelada_deja_al_gym_fuera(): void
    {
        $gym = $this->gymConSuscripcion();
        $gym->activeSubscription()->update(['status' => GymSubscription::STATUS_CANCELLED]);

        $gym->refresh();

        $this->assertFalse($gym->is_active);
    }

    public function test_renovar_devuelve_el_acceso_a_un_gym_vencido(): void
    {
        $gym = $this->gymConSuscripcion();
        $gym->activeSubscription()->update(['ends_at' => now()->subDay()]);
        $gym->refresh();

        $this->assertFalse($gym->is_active);

        $gym->subscriptions()->create([
            'subscription_plan_id' => SubscriptionPlan::first()->id,
            'starts_at' => now()->toDateString(),
        ]);

        $gym->refresh();

        $this->assertTrue($gym->is_active);
    }

    public function test_los_scopes_separan_activos_suspendidos_y_vencidos(): void
    {
        $activo = $this->gymConSuscripcion();

        $suspendido = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gym Dos'],
            ownerData: ['name' => 'Dos', 'username' => 'dos', 'email' => 'dos@test.test', 'password' => 'secret123'],
            subscriptionData: ['subscription_plan_id' => SubscriptionPlan::first()->id, 'starts_at' => now()->toDateString()],
        );
        $suspendido->update(['suspended_at' => now()]);

        $vencido = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gym Tres'],
            ownerData: ['name' => 'Tres', 'username' => 'tres', 'email' => 'tres@test.test', 'password' => 'secret123'],
            subscriptionData: ['subscription_plan_id' => SubscriptionPlan::first()->id, 'starts_at' => now()->subDays(60)->toDateString()],
        );

        $this->assertEqualsCanonicalizing([$activo->id], Gym::active()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$suspendido->id], Gym::suspended()->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$suspendido->id, $vencido->id],
            Gym::inactive()->pluck('id')->all(),
        );
    }

    public function test_el_superadmin_da_de_alta_un_gym_con_su_suscripcion(): void
    {
        $this->superAdmin();
        $plan = $this->plan(days: 365, name: 'Anual');

        Livewire::test(CreateGym::class)
            ->fillForm([
                'name' => 'Gym Nuevo',
                'timezone' => 'America/Mexico_City',
                'subscription_plan_id' => $plan->id,
                'subscription_starts_at' => now()->toDateString(),
                'owner_name' => 'Dueño Nuevo',
                'owner_username' => 'nuevo',
                'owner_email' => 'nuevo@test.test',
                'owner_password' => 'secret123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $gym = Gym::where('name', 'Gym Nuevo')->first();

        $this->assertNotNull($gym);
        $this->assertTrue($gym->is_active);
        $this->assertSame($plan->id, $gym->activeSubscription()->subscription_plan_id);
    }

    public function test_suspender_y_reactivar_desde_el_listado(): void
    {
        $this->superAdmin();
        $gym = $this->gymConSuscripcion();

        Livewire::test(ListGyms::class)
            ->callAction(TestAction::make('toggleSuspension')->table($gym));

        $this->assertNotNull($gym->refresh()->suspended_at);

        Livewire::test(ListGyms::class)
            ->callAction(TestAction::make('toggleSuspension')->table($gym));

        $this->assertNull($gym->refresh()->suspended_at);
    }

    public function test_no_se_borra_un_plan_ya_contratado(): void
    {
        $gym = $this->gymConSuscripcion();
        $plan = $gym->activeSubscription()->plan;

        $this->assertFalse($plan->isDeletable());
        $this->assertFalse($plan->delete());
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    public function test_se_borra_un_plan_que_nadie_contrato(): void
    {
        $this->plan(days: 7, name: 'Semanal')->delete();

        $this->assertDatabaseMissing('subscription_plans', ['name' => 'Semanal']);
    }

    public function test_el_listado_de_planes_carga(): void
    {
        $this->superAdmin();
        $this->plan();

        Livewire::test(ListSubscriptionPlans::class)
            ->assertSuccessful();
    }
}
