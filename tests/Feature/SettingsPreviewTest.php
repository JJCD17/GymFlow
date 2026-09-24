<?php

namespace Tests\Feature;

use App\Actions\CreateGymWithOwner;
use App\Filament\Pages\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-16 10:00:00'));

        $gym = app(CreateGymWithOwner::class)->handle(
            gymData: ['name' => 'Gym Titanes'],
            ownerData: ['name' => 'Dueño', 'username' => 'titanes', 'email' => 'titanes@test.test', 'password' => 'secret123'],
        );

        $this->actingAs(User::where('gym_id', $gym->id)->firstOrFail());
    }

    public function test_la_vista_previa_muestra_el_mensaje_ya_llenado(): void
    {
        Livewire::test(Settings::class)
            ->assertSee('Así le llega a tu cliente')
            ->assertSee('Hola Ana López, te recordamos que tu membresía en Gym Titanes vence el 23/09/2026.', escape: false);
    }

    public function test_la_vista_previa_cambia_al_escribir_y_con_los_dias(): void
    {
        Livewire::test(Settings::class)
            ->set('data.expiring_days', 3)
            ->set('data.message_expiring', 'Ey {cliente}, vence el {vencimiento}')
            ->assertSee('Ey Ana López, vence el 19/09/2026');
    }

    public function test_la_vista_previa_escapa_el_html(): void
    {
        Livewire::test(Settings::class)
            ->set('data.message_inactive', '<b>Hola</b> {cliente}')
            ->assertDontSee('<b>Hola</b>', escape: false)
            ->assertSee('&lt;b&gt;Hola&lt;/b&gt; Ana López', escape: false);
    }
}
