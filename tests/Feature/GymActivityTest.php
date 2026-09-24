<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Filament\Superadmin\Widgets\GymActivityWidget;
use App\Http\Middleware\RecordLastSeen;
use App\Models\Gym;
use App\Models\Member;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\GymActivity;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class GymActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));
    }

    protected function gym(string $name, int $registradoHaceDias = 30): Gym
    {
        $plan = SubscriptionPlan::firstOrCreate(
            ['name' => 'Anual'],
            ['duration_days' => 365, 'price' => 5000],
        );

        $slug = str($name)->slug()->toString();

        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => $name],
            ownerData: [
                'name' => "Dueño {$name}",
                'username' => $slug,
                'email' => "{$slug}@test.test",
                'password' => 'secret123',
            ],
            subscriptionData: [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
            ],
        );

        $gym->forceFill(['created_at' => now()->subDays($registradoHaceDias)])->save();

        return $gym;
    }

    protected function clientes(Gym $gym, int $cuantos, Carbon $fecha): void
    {
        foreach (range(1, $cuantos) as $i) {
            $member = Member::create(['gym_id' => $gym->id, 'full_name' => "Cliente {$i}"]);
            $member->forceFill(['created_at' => $fecha])->save();
        }
    }

    protected function row(Gym $gym): array
    {
        return GymActivity::forActiveGyms()->firstWhere('gym.id', $gym->id);
    }

    public function test_gimnasio_que_nunca_se_ha_usado_queda_sin_estrenar(): void
    {
        $gym = $this->gym('Gym Fantasma', registradoHaceDias: 10);

        $row = $this->row($gym);

        $this->assertSame(GymActivity::STATUS_UNSTARTED, $row['status']);
        $this->assertSame('Se registró hace 10 días y todavía no ha usado GymFlow.', $row['message']);
    }

    public function test_recien_registrado_sin_actividad_tiene_dias_de_gracia(): void
    {
        $gym = $this->gym('Gym Nuevo', registradoHaceDias: 1);

        $this->assertSame(GymActivity::STATUS_OK, $this->row($gym)['status']);
    }

    public function test_cuenta_los_dias_desde_la_ultima_actividad(): void
    {
        $gym = $this->gym('Gym Dormido');
        $this->clientes($gym, 8, now()->subDays(20));

        $member = $gym->members()->first();
        $member->checkIns()->create(['gym_id' => $gym->id, 'checked_in_at' => now()->subDays(12)]);

        $row = $this->row($gym);

        $this->assertSame(GymActivity::STATUS_IDLE, $row['status']);
        $this->assertSame(12, $row['idleDays']);
        $this->assertSame('Lleva 12 días sin utilizar GymFlow.', $row['message']);
    }

    public function test_el_login_del_dueno_cuenta_como_actividad(): void
    {
        $gym = $this->gym('Gym Login');
        $this->clientes($gym, 8, now()->subDays(20));

        $owner = User::where('gym_id', $gym->id)->firstOrFail();
        event(new Login('web', $owner, false));

        $row = $this->row($gym);

        $this->assertSame(GymActivity::STATUS_OK, $row['status']);
        $this->assertSame(0, $row['idleDays']);
        $this->assertTrue($row['signals']['Login del dueño']->isToday());
    }

    public function test_entra_pero_casi_no_registra_es_poco_uso(): void
    {
        $gym = $this->gym('Gym Tibio', registradoHaceDias: 40);
        $this->clientes($gym, 2, now()->subDays(2));

        $row = $this->row($gym);

        $this->assertSame(GymActivity::STATUS_LOW_USE, $row['status']);
        $this->assertSame('Entra, pero casi no registra: 2 clientes y 0 asistencias en 14 días.', $row['message']);
    }

    public function test_gimnasios_suspendidos_no_se_evaluan(): void
    {
        $gym = $this->gym('Gym Pausado');
        $gym->update(['suspended_at' => now()]);

        $this->assertNull(GymActivity::forActiveGyms()->firstWhere('gym.id', $gym->id));
    }

    public function test_los_mas_abandonados_van_primero(): void
    {
        $poco = $this->gym('Gym Poco');
        $this->clientes($poco, 8, now()->subDays(8));

        $mucho = $this->gym('Gym Mucho');
        $this->clientes($mucho, 8, now()->subDays(25));

        $nunca = $this->gym('Gym Nunca');

        $orden = GymActivity::forActiveGyms()->pluck('gym.id')->all();

        $this->assertSame([$nunca->id, $mucho->id, $poco->id], $orden);
    }

    public function test_la_interaccion_se_registra_como_mucho_cada_cinco_minutos(): void
    {
        $gym = $this->gym('Gym Clics');
        $owner = User::where('gym_id', $gym->id)->firstOrFail();

        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $owner);
        $middleware = new RecordLastSeen;
        $next = fn () => response('ok');

        $middleware->handle($request, $next);
        $primera = $owner->fresh()->last_seen_at;
        $this->assertTrue($primera->equalTo(now()));

        $this->travel(2)->minutes();
        $middleware->handle($request, $next);
        $this->assertTrue($owner->fresh()->last_seen_at->equalTo($primera));

        $this->travel(4)->minutes();
        $middleware->handle($request, $next);
        $this->assertTrue($owner->fresh()->last_seen_at->equalTo(now()));
    }

    public function test_el_widget_muestra_a_quien_necesita_atencion(): void
    {
        $this->gym('Gym Fantasma', registradoHaceDias: 10);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));

        Livewire::test(GymActivityWidget::class)
            ->assertSee('Gym Fantasma')
            ->assertSee('todavía no ha usado GymFlow');
    }
}
