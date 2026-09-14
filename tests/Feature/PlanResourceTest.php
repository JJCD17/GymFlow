<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Actions\RegisterMembership;
use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Filament\Resources\Plans\Tables\PlansTable;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function gym(string $name, string $slug): Gym
    {
        return app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => $name],
            ownerData: [
                'name' => "Dueño {$name}",
                'username' => $slug,
                'email' => "{$slug}@test.test",
                'password' => 'secret123',
            ],
        );
    }

    protected function actingAsOwnerOf(Gym $gym): User
    {
        $owner = User::where('gym_id', $gym->id)->firstOrFail();

        $this->actingAs($owner);

        return $owner;
    }

    public function test_el_dueno_puede_crear_un_plan(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        Livewire::test(CreatePlan::class)
            ->fillForm([
                'name' => 'Quincenal',
                'price' => 250,
                'duration_days' => 15,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $plan = Plan::where('name', 'Quincenal')->first();

        $this->assertNotNull($plan);
        $this->assertEquals(250, $plan->price);
        $this->assertSame(15, $plan->duration_days);
        $this->assertSame($gym->id, $plan->gym_id);
    }

    public function test_el_dueno_puede_ajustar_precio_y_tipo_de_un_plan(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $plan = Plan::where('name', 'Mensual')->firstOrFail();

        Livewire::test(EditPlan::class, ['record' => $plan->getRouteKey()])
            ->fillForm([
                'name' => 'Mensual Estudiantes',
                'price' => 320,
                'duration_days' => 28,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $plan->refresh();

        $this->assertSame('Mensual Estudiantes', $plan->name);
        $this->assertEquals(320, $plan->price);
        $this->assertSame(28, $plan->duration_days);
    }

    public function test_el_formulario_rechaza_datos_incompletos(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        Livewire::test(CreatePlan::class)
            ->fillForm(['name' => '', 'price' => null, 'duration_days' => 0])
            ->call('create')
            ->assertHasFormErrors(['name', 'price', 'duration_days']);
    }

    public function test_el_listado_solo_muestra_los_planes_del_propio_gimnasio(): void
    {
        $uno = $this->gym('Gym Uno', 'uno');
        $dos = $this->gym('Gym Dos', 'dos');

        $ajeno = Plan::withoutGlobalScope('gym')
            ->where('gym_id', $dos->id)
            ->firstOrFail();

        $this->actingAsOwnerOf($uno);

        Livewire::test(ListPlans::class)
            ->assertCanSeeTableRecords(Plan::all())
            ->assertCanNotSeeTableRecords([$ajeno]);

        $this->assertNull(Plan::find($ajeno->id));
    }

    public function test_desactivar_un_plan_lo_saca_de_las_listas_de_cobro(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $plan = Plan::where('name', 'Mensual')->firstOrFail();

        Livewire::test(ListPlans::class)
            ->callTableAction('toggleActive', $plan);

        $this->assertFalse($plan->refresh()->is_active);
        $this->assertNotContains('Mensual', Plan::active()->pluck('name')->all());
    }

    public function test_el_boton_de_borrar_sigue_visible_y_avisa_por_que_no_se_puede(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $vendido = Plan::where('name', 'Mensual')->firstOrFail();
        $libre = Plan::where('name', 'Anual')->firstOrFail();

        $member = Member::create(['full_name' => 'Cliente Prueba']);
        app(RegisterMembership::class)->handle($member, $vendido, 400, 'cash');

        Livewire::test(ListPlans::class)
            ->assertTableActionVisible('delete', $vendido)
            ->assertTableActionVisible('delete', $libre);

        $aviso = PlansTable::deleteDescription($vendido);

        $this->assertStringContainsString('una membresía vendida', $aviso);
        $this->assertStringContainsString('desactívalo', $aviso);
        $this->assertStringContainsString(
            'no se puede deshacer',
            PlansTable::deleteDescription($libre)
        );
    }

    public function test_intentar_borrar_un_plan_vendido_no_lo_borra(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $vendido = Plan::where('name', 'Mensual')->firstOrFail();
        $member = Member::create(['full_name' => 'Cliente Prueba']);
        $membership = app(RegisterMembership::class)->handle($member, $vendido, 400, 'cash');

        $this->assertFalse($vendido->isDeletable());
        $this->assertFalse($vendido->delete());

        $this->assertModelExists($vendido);
        $this->assertModelExists($membership);
    }

    public function test_un_plan_nunca_vendido_si_se_borra(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $libre = Plan::where('name', 'Anual')->firstOrFail();

        $this->assertTrue($libre->isDeletable());

        Livewire::test(ListPlans::class)
            ->callTableAction('delete', $libre);

        $this->assertModelMissing($libre);
    }

    public function test_el_dueno_reordena_los_planes_arrastrandolos(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $orden = Plan::orderBy('sort_order')->pluck('id')->all();

        // Mover el último al primer lugar, como al arrastrarlo arriba.
        $ultimo = array_pop($orden);
        array_unshift($orden, $ultimo);

        Livewire::test(ListPlans::class)
            ->call('reorderTable', $orden);

        $this->assertSame(
            $orden,
            Plan::orderBy('sort_order')->pluck('id')->all()
        );

        // El nuevo orden es el que se ve al cobrar.
        $this->assertSame(
            Plan::find($ultimo)->name,
            Plan::active()->orderBy('sort_order')->first()->name
        );
    }

    public function test_un_plan_nuevo_se_agrega_al_final_de_la_lista(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ultimoOrden = Plan::max('sort_order');

        Livewire::test(CreatePlan::class)
            ->fillForm([
                'name' => 'Semanal',
                'price' => 150,
                'duration_days' => 7,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $nuevo = Plan::where('name', 'Semanal')->firstOrFail();

        $this->assertSame($ultimoOrden + 1, $nuevo->sort_order);
        $this->assertSame('Semanal', Plan::orderBy('sort_order')->get()->last()->name);
    }

    public function test_cada_gimnasio_numera_su_orden_por_separado(): void
    {
        $uno = $this->gym('Gym Uno', 'uno');
        $dos = $this->gym('Gym Dos', 'dos');

        $this->actingAsOwnerOf($dos);
        $nuevo = Plan::create(['name' => 'Semanal', 'price' => 150, 'duration_days' => 7]);

        // El orden se calcula entre los planes del propio gimnasio, sin que
        // los del otro empujen la numeración.
        $this->assertSame(
            Plan::withoutGlobalScope('gym')->where('gym_id', $dos->id)->where('id', '!=', $nuevo->id)->max('sort_order') + 1,
            $nuevo->sort_order
        );
        $this->assertSame($dos->id, $nuevo->gym_id);
    }

    public function test_desde_el_aviso_se_puede_desactivar_en_su_lugar(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $vendido = Plan::where('name', 'Mensual')->firstOrFail();
        $member = Member::create(['full_name' => 'Cliente Prueba']);
        app(RegisterMembership::class)->handle($member, $vendido, 400, 'cash');

        Livewire::test(ListPlans::class)
            ->callAction([
                TestAction::make('delete')->table($vendido),
                TestAction::make('deactivateInstead'),
            ]);

        $this->assertFalse($vendido->refresh()->is_active);
        $this->assertModelExists($vendido);
    }

    public function test_cambiar_el_precio_no_altera_los_pagos_ya_registrados(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $plan = Plan::where('name', 'Mensual')->firstOrFail();
        $member = Member::create(['full_name' => 'Cliente Prueba']);
        $membership = app(RegisterMembership::class)->handle($member, $plan, 400, 'cash');

        $plan->update(['price' => 500, 'duration_days' => 60]);

        $this->assertEquals(400, $membership->payments()->sum('amount'));
        $this->assertTrue($membership->refresh()->ends_at->equalTo(
            $membership->starts_at->copy()->addDays(30)
        ));
    }
}
