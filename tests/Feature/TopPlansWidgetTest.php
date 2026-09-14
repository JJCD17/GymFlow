<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Actions\RegisterMembership;
use App\Filament\Widgets\TopPlansWidget;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\PlanSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TopPlansWidgetTest extends TestCase
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

    protected function vender(string $plan, float $monto, ?string $cliente = null): void
    {
        app(RegisterMembership::class)->handle(
            member: Member::create(['full_name' => $cliente ?? 'Cliente '.uniqid()]),
            plan: Plan::where('name', $plan)->firstOrFail(),
            amount: $monto,
            method: 'cash',
        );
    }

    public function test_ordena_los_planes_por_numero_de_ventas(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->vender('Mensual', 400);
        $this->vender('Mensual', 400);
        $this->vender('Mensual', 400);
        $this->vender('Anual', 3600);

        $filas = PlanSales::forRange('30');

        // Mensual vendió más aunque Anual dejó más dinero.
        $this->assertSame('Mensual', $filas->first()['plan']->name);
        $this->assertSame(3, $filas->first()['ventas']);
        $this->assertSame(1200.0, $filas->first()['ingresos']);

        $this->assertSame('Anual', $filas[1]['plan']->name);
        $this->assertSame(3600.0, $filas[1]['ingresos']);
    }

    public function test_muestra_los_planes_sin_ventas_en_cero_al_final(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->vender('Mensual', 400);

        $filas = PlanSales::forRange('30');

        // Todos los planes aparecen: ver un plan en cero es la señal de cuál
        // hay que ajustar.
        $this->assertCount(Plan::count(), $filas);

        $sinVentas = $filas->filter(fn (array $f) => $f['ventas'] === 0);

        $this->assertNotEmpty($sinVentas);
        $this->assertTrue($sinVentas->every(fn (array $f) => $f['ingresos'] === 0.0));
        $this->assertSame(0, $filas->last()['ventas']);
    }

    public function test_el_rango_de_fechas_acota_lo_que_se_cuenta(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->vender('Mensual', 400);

        // Una venta vieja: dentro del año, fuera de los últimos 30 días.
        $this->travel(-60)->days();
        $this->vender('Anual', 3600);
        $this->travelBack();

        $reciente = PlanSales::forRange('30');
        $this->assertSame(1, $reciente->sum('ventas'));
        $this->assertSame(400.0, $reciente->sum('ingresos'));

        $historico = PlanSales::forRange('all');
        $this->assertSame(2, $historico->sum('ventas'));
        $this->assertSame(4000.0, $historico->sum('ingresos'));
    }

    public function test_los_ingresos_reflejan_lo_cobrado_no_el_precio_de_lista(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        // Un descuento: el plan cuesta 400 pero se cobraron 350.
        $this->vender('Mensual', 350);

        $mensual = PlanSales::forRange('30')->firstWhere(fn (array $f) => $f['plan']->name === 'Mensual');

        $this->assertSame(350.0, $mensual['ingresos']);
        $this->assertEquals(400, Plan::where('name', 'Mensual')->value('price'));
    }

    public function test_cada_gimnasio_ve_solo_sus_propias_ventas(): void
    {
        $uno = $this->gym('Gym Uno', 'uno');
        $dos = $this->gym('Gym Dos', 'dos');

        $this->actingAsOwnerOf($dos);
        $this->vender('Mensual', 400, 'Cliente del dos');

        $this->actingAsOwnerOf($uno);

        $this->assertSame(0, PlanSales::forRange('all')->sum('ventas'));
        $this->assertSame(0.0, PlanSales::forRange('all')->sum('ingresos'));

        $this->actingAsOwnerOf($dos);

        $this->assertSame(1, PlanSales::forRange('all')->sum('ventas'));
    }

    public function test_un_pago_sin_membresia_no_infla_los_ingresos_de_ningun_plan(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->vender('Mensual', 400);

        // Un cobro suelto, sin membresía: no pertenece a ningún plan.
        Payment::create([
            'member_id' => Member::first()->id,
            'membership_id' => null,
            'amount' => 999,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->assertSame(400.0, PlanSales::forRange('all')->sum('ingresos'));
    }

    public function test_el_widget_se_dibuja_con_sus_cifras_y_cambia_de_rango(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->vender('Mensual', 400);

        Livewire::test(TopPlansWidget::class)
            ->assertSee('Planes más vendidos')
            ->assertSee('Mensual')
            ->assertSee('$400.00')
            ->assertSee('Ajustar mis planes')
            ->set('range', 'all')
            ->assertSee('Mensual');
    }

    public function test_sin_ventas_en_el_periodo_lo_dice_en_vez_de_mostrar_una_tabla_vacia(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        Livewire::test(TopPlansWidget::class)
            ->assertSee('No se vendió ninguna membresía en este periodo.');
    }
}
