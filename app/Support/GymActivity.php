<?php

namespace App\Support;

use App\Models\Gym;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Qué tanto usa GymFlow cada gimnasio activo, más allá de poder entrar.
 */
class GymActivity
{
    public const IDLE_DAYS = 7;

    public const GRACE_DAYS = 3;

    public const LOW_USE_WINDOW_DAYS = 14;

    public const LOW_USE_MEMBERS = 5;

    public const LOW_USE_CHECK_INS = 10;

    public const STATUS_UNSTARTED = 'unstarted';

    public const STATUS_IDLE = 'idle';

    public const STATUS_LOW_USE = 'low_use';

    public const STATUS_OK = 'ok';

    /** Orden en que se muestran: primero lo que más urge. */
    protected const PRIORITY = [
        self::STATUS_UNSTARTED => 0,
        self::STATUS_IDLE => 1,
        self::STATUS_LOW_USE => 2,
        self::STATUS_OK => 3,
    ];

    /**
     * @return Collection<int, array{
     *     gym: Gym,
     *     status: string,
     *     message: string,
     *     lastActivity: ?Carbon,
     *     idleDays: ?int,
     *     signals: array<string, ?Carbon>,
     * }>
     */
    public static function forActiveGyms(): Collection
    {
        // Suspendidos y vencidos no pueden entrar: su inactividad no dice nada.
        return Gym::active()
            ->withMax('owner', 'last_login_at')
            ->withMax('users', 'last_seen_at')
            ->withMax('members', 'created_at')
            ->withMax('checkIns', 'checked_in_at')
            ->withMax('payments', 'paid_at')
            ->withCount('members')
            ->withCount(['checkIns as recent_check_ins_count' => fn ($query) => $query
                ->where('checked_in_at', '>=', now()->subDays(self::LOW_USE_WINDOW_DAYS))])
            ->get()
            ->map(fn (Gym $gym) => self::describe($gym))
            ->sortBy([
                fn ($a, $b) => self::PRIORITY[$a['status']] <=> self::PRIORITY[$b['status']],
                fn ($a, $b) => ($b['idleDays'] ?? PHP_INT_MAX) <=> ($a['idleDays'] ?? PHP_INT_MAX),
            ])
            ->values();
    }

    protected static function describe(Gym $gym): array
    {
        $signals = [
            'Login del dueño' => self::date($gym->owner_max_last_login_at),
            'Interacción' => self::date($gym->users_max_last_seen_at),
            'Cliente registrado' => self::date($gym->members_max_created_at),
            'Asistencia' => self::date($gym->check_ins_max_checked_in_at),
            'Pago' => self::date($gym->payments_max_paid_at),
        ];

        $lastActivity = collect($signals)->filter()->max();
        $idleDays = $lastActivity ? self::daysSince($lastActivity) : null;
        $ageDays = self::daysSince($gym->created_at);

        [$status, $message] = match (true) {
            $lastActivity === null && $ageDays >= self::GRACE_DAYS => [
                self::STATUS_UNSTARTED,
                "Se registró hace {$ageDays} días y todavía no ha usado GymFlow.",
            ],
            $lastActivity === null => [
                self::STATUS_OK,
                'Recién registrado.',
            ],
            $idleDays >= self::IDLE_DAYS => [
                self::STATUS_IDLE,
                "Lleva {$idleDays} días sin utilizar GymFlow.",
            ],
            $ageDays >= self::LOW_USE_WINDOW_DAYS
                && $gym->members_count < self::LOW_USE_MEMBERS
                && $gym->recent_check_ins_count < self::LOW_USE_CHECK_INS => [
                    self::STATUS_LOW_USE,
                    sprintf(
                        'Entra, pero casi no registra: %d %s y %d %s en %d días.',
                        $gym->members_count,
                        $gym->members_count === 1 ? 'cliente' : 'clientes',
                        $gym->recent_check_ins_count,
                        $gym->recent_check_ins_count === 1 ? 'asistencia' : 'asistencias',
                        self::LOW_USE_WINDOW_DAYS,
                    ),
                ],
            default => [
                self::STATUS_OK,
                match ($idleDays) {
                    0 => 'Usó GymFlow hoy.',
                    1 => 'Usó GymFlow ayer.',
                    default => "Usó GymFlow hace {$idleDays} días.",
                },
            ],
        };

        return [
            'gym' => $gym,
            'status' => $status,
            'message' => $message,
            'lastActivity' => $lastActivity,
            'idleDays' => $idleDays,
            'signals' => $signals,
        ];
    }

    protected static function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    protected static function daysSince(Carbon $date): int
    {
        return (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay());
    }
}
