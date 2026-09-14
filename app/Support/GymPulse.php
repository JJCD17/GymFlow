<?php

namespace App\Support;

use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cómo está el gimnasio hoy: quién vino, cuánta gente hay al corriente y
 * quién se está perdiendo.
 */
class GymPulse
{
    public function __construct(protected Gym $gym) {}

    public static function for(Gym $gym): self
    {
        return new self($gym);
    }

    public function isClosedToday(): bool
    {
        return $this->gym->isClosedOn(now());
    }

    public function checkInsToday(): int
    {
        return CheckIn::today()->count();
    }

    /**
     * Personas distintas que vinieron hoy. Solo se permite una asistencia por
     * día, pero contar personas es lo que el dueño quiere saber.
     */
    public function peopleToday(): int
    {
        return CheckIn::today()->distinct('member_id')->count('member_id');
    }

    public function activeMembers(): int
    {
        return Membership::active()->distinct('member_id')->count('member_id');
    }

    /**
     * Clientes al corriente que aún no han venido hoy: los que podrían llegar.
     */
    public function pendingToday(): int
    {
        return max(0, $this->activeMembers() - $this->peopleToday());
    }

    public function absentMembers(): int
    {
        return Member::active()
            ->withMembershipStatus('active')
            ->inactiveSince($this->gym->absenceThresholdDate())
            ->count();
    }

    /**
     * El día abierto anterior, para comparar contra algo comparable: si el
     * gimnasio cierra domingos, el lunes no debe compararse contra el domingo.
     */
    public function previousOpenDay(): Carbon
    {
        $date = now()->startOfDay()->subDay();

        while ($this->gym->isClosedOn($date)) {
            $date->subDay();
        }

        return $date;
    }

    public function peopleOnPreviousOpenDay(): int
    {
        return CheckIn::whereDate('checked_in_at', $this->previousOpenDay())
            ->distinct('member_id')
            ->count('member_id');
    }

    /**
     * Asistencia de los últimos días abiertos, del más viejo al más reciente.
     *
     * @return Collection<int, array{fecha: Carbon, personas: int}>
     */
    public function recentDays(int $days = 14): Collection
    {
        $desde = now()->startOfDay()->subDays($days);

        $porDia = CheckIn::where('checked_in_at', '>=', $desde)
            ->selectRaw('date(checked_in_at) as dia, count(distinct member_id) as personas')
            ->groupBy('dia')
            ->pluck('personas', 'dia');

        return collect(range($days, 0))
            ->map(fn (int $atras) => now()->startOfDay()->subDays($atras))
            ->reject(fn (Carbon $fecha) => $this->gym->isClosedOn($fecha))
            ->map(fn (Carbon $fecha) => [
                'fecha' => $fecha,
                'personas' => (int) ($porDia[$fecha->toDateString()] ?? 0),
            ])
            ->values();
    }

    /**
     * Quiénes están en el gimnasio hoy, del más reciente al primero.
     *
     * @return Collection<int, CheckIn>
     */
    public function todaysVisitors(int $limit = 8): Collection
    {
        return CheckIn::with('member')
            ->today()
            ->orderByDesc('checked_in_at')
            ->limit($limit)
            ->get();
    }
}
