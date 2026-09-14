<?php

namespace App\Support;

use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Cuántas membresías se vendieron de cada plan y cuánto dejaron, en un periodo.
 */
class PlanSales
{
    public const RANGES = [
        'month' => 'Este mes',
        '30' => 'Últimos 30 días',
        '90' => 'Últimos 90 días',
        'year' => 'Este año',
        'all' => 'Todo el historial',
    ];

    public static function rangeLabel(string $range): string
    {
        return self::RANGES[$range] ?? self::RANGES['30'];
    }

    /**
     * El inicio del periodo. `null` significa sin límite (todo el historial).
     */
    public static function startsAt(string $range): ?Carbon
    {
        return match ($range) {
            'month' => now()->startOfMonth(),
            '90' => now()->subDays(90)->startOfDay(),
            'year' => now()->startOfYear(),
            'all' => null,
            default => now()->subDays(30)->startOfDay(),
        };
    }

    /**
     * Una fila por plan del gimnasio, incluidos los que no vendieron nada:
     * ver un plan en cero es justo la señal de cuál hay que ajustar.
     *
     * @return Collection<int, array{plan: Plan, ventas: int, ingresos: float}>
     */
    public static function forRange(string $range): Collection
    {
        $desde = self::startsAt($range);

        // La venta se cuenta por la membresía y el dinero por los pagos: un
        // pago guarda su propio monto, que puede traer descuento.
        $ventas = Membership::query()
            ->when($desde, fn ($query) => $query->where('created_at', '>=', $desde))
            ->selectRaw('plan_id, count(*) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        $ingresos = Payment::query()
            ->join('memberships', 'payments.membership_id', '=', 'memberships.id')
            ->when($desde, fn ($query) => $query->where('payments.paid_at', '>=', $desde))
            ->selectRaw('memberships.plan_id, sum(payments.amount) as total')
            ->groupBy('memberships.plan_id')
            ->pluck('total', 'plan_id');

        return Plan::orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'plan' => $plan,
                'ventas' => (int) ($ventas[$plan->id] ?? 0),
                'ingresos' => (float) ($ingresos[$plan->id] ?? 0),
            ])
            // Lo más vendido arriba; entre dos planes sin ventas, manda el
            // orden que el dueño les dio.
            ->sortByDesc(fn (array $fila) => [$fila['ventas'], $fila['ingresos']])
            ->values();
    }
}
