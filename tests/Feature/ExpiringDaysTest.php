<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Filament\Pages\Settings;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ExpiringDaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));
    }

    protected function gymConDias(int $dias, string $slug = 'uno'): Gym
    {
        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => "Gym {$slug}"],
            ownerData: [
                'name' => "Dueño {$slug}",
                'username' => $slug,
                'email' => "{$slug}@test.test",
                'password' => 'secret123',
            ],
        );

        $gym->update(['expiring_days' => $dias]);

        $this->actingAs(User::where('gym_id', $gym->id)->firstOrFail());

        return $gym;
    }

    protected function clienteQueVenceEn(int $dias): Member
    {
        $member = Member::create(['full_name' => "Vence en {$dias}"]);

        Membership::create([
            'member_id' => $member->id,
            'plan_id' => Plan::firstOrFail()->id,
            'starts_at' => now()->subDays(20)->toDateString(),
            'ends_at' => now()->addDays($dias)->toDateString(),
        ]);

        return $member->fresh();
    }

    public function test_el_nuevo_gimnasio_arranca_con_siete_dias(): void
    {
        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gym Nuevo'],
            ownerData: ['name' => 'Dueño', 'username' => 'nuevo', 'email' => 'nuevo@test.test', 'password' => 'secret123'],
        );

        $this->assertSame(Gym::DEFAULT_EXPIRING_DAYS, $gym->fresh()->expiring_days);
    }

    public function test_la_etiqueta_usa_los_dias_del_gimnasio(): void
    {
        $this->gymConDias(3);
        $member = $this->clienteQueVenceEn(5);

        $this->assertSame('active', $member->membership_status);

        $member->gym->update(['expiring_days' => 15]);

        $this->assertSame('expiring', $member->fresh()->membership_status);
    }

    public function test_el_ultimo_dia_cuenta_igual_en_filtro_y_etiqueta(): void
    {
        $this->gymConDias(10);

        $enElBorde = $this->clienteQueVenceEn(10);
        $unDiaDespues = $this->clienteQueVenceEn(11);

        $this->assertSame('expiring', $enElBorde->membership_status);
        $this->assertSame('active', $unDiaDespues->membership_status);

        $filtrados = Member::withMembershipStatus('expiring', 10)->pluck('id')->all();

        $this->assertSame([$enElBorde->id], $filtrados);
    }

    public function test_el_dia_que_vence_sigue_por_vencer(): void
    {
        $this->gymConDias(7);
        $member = $this->clienteQueVenceEn(0);

        $this->assertSame('expiring', $member->membership_status);
        $this->assertSame([$member->id], Member::withMembershipStatus('expiring', 7)->pluck('id')->all());
    }

    public function test_ajustes_guarda_los_dias(): void
    {
        $gym = $this->gymConDias(7);

        Livewire::test(Settings::class)
            ->assertSet('data.expiring_days', 7)
            ->set('data.expiring_days', 12)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(12, $gym->fresh()->expiring_days);
    }

    public function test_ajustes_no_acepta_valores_fuera_de_rango(): void
    {
        $gym = $this->gymConDias(7);

        Livewire::test(Settings::class)
            ->set('data.expiring_days', 0)
            ->call('save')
            ->assertHasErrors(['data.expiring_days']);

        Livewire::test(Settings::class)
            ->set('data.expiring_days', 45)
            ->call('save')
            ->assertHasErrors(['data.expiring_days']);

        $this->assertSame(7, $gym->fresh()->expiring_days);
    }
}
