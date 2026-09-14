<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Actions\RegisterMembership;
use App\Filament\Widgets\TodayPulseWidget;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use App\Support\GymPulse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TodayPulseWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Un miércoles, para que "el día abierto anterior" sea el martes y no
        // dependa del día en que se corran las pruebas.
        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));
    }

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

    protected function cliente(string $nombre): Member
    {
        $member = Member::create(['full_name' => $nombre]);

        app(RegisterMembership::class)->handle(
            member: $member,
            plan: Plan::where('name', 'Mensual')->firstOrFail(),
            amount: 400,
            method: 'cash',
        );

        return $member;
    }

    public function test_cuenta_las_personas_que_vinieron_hoy(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana');
        $beto = $this->cliente('Beto');
        $this->cliente('Carla');

        $ana->checkIns()->create(['checked_in_at' => now()]);
        $beto->checkIns()->create(['checked_in_at' => now()]);

        $pulse = GymPulse::for($gym);

        $this->assertSame(2, $pulse->peopleToday());
        $this->assertSame(3, $pulse->activeMembers());
        $this->assertSame(1, $pulse->pendingToday());
    }

    public function test_compara_contra_el_ultimo_dia_abierto_no_contra_ayer(): void
    {
        // Cierra domingos y sábados: el lunes debe compararse contra el viernes.
        $gym = $this->gym('Gym Uno', 'uno');
        $gym->update(['closed_weekdays' => [0, 6]]);

        $this->actingAsOwnerOf($gym);
        $this->travelTo(Carbon::parse('2026-09-21 10:00:00')); // lunes

        $ana = $this->cliente('Ana');
        $ana->checkIns()->create(['checked_in_at' => Carbon::parse('2026-09-18 09:00:00')]); // viernes

        $pulse = GymPulse::for($gym->refresh());

        $this->assertSame('2026-09-18', $pulse->previousOpenDay()->toDateString());
        $this->assertSame(1, $pulse->peopleOnPreviousOpenDay());
    }

    public function test_la_grafica_omite_los_dias_que_el_gimnasio_cierra(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $gym->update(['closed_weekdays' => [0, 6]]);

        $this->actingAsOwnerOf($gym);

        $dias = GymPulse::for($gym->refresh())->recentDays(14);

        $this->assertNotEmpty($dias);
        $this->assertTrue(
            $dias->every(fn (array $d) => ! in_array($d['fecha']->dayOfWeek, [0, 6], true)),
            'La gráfica no debe incluir días cerrados.'
        );
    }

    public function test_avisa_cuando_el_gimnasio_no_abre_hoy(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $gym->update(['closed_weekdays' => [0]]);

        $this->actingAsOwnerOf($gym);
        $this->travelTo(Carbon::parse('2026-09-20 10:00:00')); // domingo

        $this->assertTrue(GymPulse::for($gym->refresh())->isClosedToday());

        Livewire::test(TodayPulseWidget::class)
            ->assertSee('Hoy el gimnasio no abre');
    }

    public function test_una_sola_asistencia_por_persona_no_infla_el_conteo(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana');

        // Dos registros del mismo día no son dos personas.
        $ana->checkIns()->create(['checked_in_at' => now()->setTime(7, 0)]);
        $ana->checkIns()->create(['checked_in_at' => now()->setTime(19, 0)]);

        $pulse = GymPulse::for($gym);

        $this->assertSame(2, $pulse->checkInsToday());
        $this->assertSame(1, $pulse->peopleToday());
    }

    public function test_cada_gimnasio_ve_solo_su_propia_asistencia(): void
    {
        $uno = $this->gym('Gym Uno', 'uno');
        $dos = $this->gym('Gym Dos', 'dos');

        $this->actingAsOwnerOf($dos);
        $this->cliente('Cliente del dos')->checkIns()->create(['checked_in_at' => now()]);

        $this->actingAsOwnerOf($uno);
        $this->assertSame(0, GymPulse::for($uno)->peopleToday());
        $this->assertSame(0, GymPulse::for($uno)->activeMembers());

        $this->actingAsOwnerOf($dos);
        $this->assertSame(1, GymPulse::for($dos)->peopleToday());
    }

    public function test_el_widget_muestra_los_kpis_y_quien_vino(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana López');
        $ana->checkIns()->create(['checked_in_at' => now()->setTime(8, 30, 45)]);

        Livewire::test(TodayPulseWidget::class)
            ->assertSee('Hoy en el gimnasio')
            ->assertSee('Vinieron hoy')
            ->assertSee('Al corriente')
            ->assertSee('Faltan por venir')
            ->assertSee('Sin asistir')
            ->assertSee('Ana López')
            // Con segundos: dos llegadas del mismo minuto se distinguen.
            ->assertSee('08:30:45')
            ->assertSee('Ver clientes y registrar asistencia');
    }

    public function test_la_barra_de_hoy_crece_con_la_asistencia_del_dia(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana');
        $beto = $this->cliente('Beto');

        // Ayer vinieron 2; hoy lleva 1, o sea media barra.
        $ana->checkIns()->create(['checked_in_at' => now()->copy()->subDay()]);
        $beto->checkIns()->create(['checked_in_at' => now()->copy()->subDay()]);
        $ana->checkIns()->create(['checked_in_at' => now()]);

        $html = Livewire::test(TodayPulseWidget::class)->html();

        preg_match_all('/gf-pulse-day-bar" style="height: ([\d.]+)%/', $html, $alturas);

        $dibujadas = array_map('floatval', $alturas[1]);

        // La barra vive dentro de un riel con alto propio; sin él el
        // porcentaje se mide contra un contenedor vacío y no se ve nada.
        $this->assertStringContainsString('gf-pulse-day-track', $html);
        $this->assertContains(100.0, $dibujadas);
        $this->assertContains(50.0, $dibujadas);
    }

    public function test_lo_dice_cuando_todavia_no_llega_nadie(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $this->cliente('Ana');

        Livewire::test(TodayPulseWidget::class)
            ->assertSee('Todavía no llega nadie.');
    }

    public function test_cuenta_como_ausente_a_quien_lleva_dias_sin_venir(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana');
        $beto = $this->cliente('Beto');

        // Ana vino hoy; Beto no viene desde hace un mes.
        $ana->checkIns()->create(['checked_in_at' => now()]);
        $beto->checkIns()->create(['checked_in_at' => now()->copy()->subDays(30)]);

        $this->assertSame(1, GymPulse::for($gym)->absentMembers());
    }

    public function test_un_check_in_registrado_ahora_se_refleja_al_refrescar(): void
    {
        $gym = $this->gym('Gym Uno', 'uno');
        $this->actingAsOwnerOf($gym);

        $ana = $this->cliente('Ana López');

        $widget = Livewire::test(TodayPulseWidget::class)
            ->assertSee('Todavía no llega nadie.');

        // Como si alguien la registrara desde la pantalla de clientes.
        CheckIn::create(['member_id' => $ana->id, 'checked_in_at' => now()]);

        $widget->call('$refresh')
            ->assertSee('Ana López')
            ->assertDontSee('Todavía no llega nadie.');
    }
}
