{{--
    Estilos en CSS plano con las variables del panel: las utilidades de Tailwind
    no llegan a las vistas de Filament. El acento es --primary-*, sin "color".

    wire:poll mantiene vivo el conteo: el escritorio se queda abierto en el
    mostrador y una asistencia registrada desde otra pantalla aparece aquí sola.
--}}
<x-filament-widgets::widget wire:poll.30s>
    <x-filament::section>
        <x-slot name="heading">Hoy en el gimnasio</x-slot>

        <x-slot name="description">
            {{ now()->translatedFormat('l d \d\e F') }}
        </x-slot>

        <style>
            .gf-pulse-kpis {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
                gap: 1rem;
            }

            .gf-pulse-kpi {
                padding: 0.875rem 1rem;
                border: 1px solid var(--color-gray-200);
                border-radius: 0.75rem;
                background-color: var(--color-gray-50);
            }

            .dark .gf-pulse-kpi {
                border-color: rgba(255, 255, 255, 0.1);
                background-color: rgba(255, 255, 255, 0.02);
            }

            .gf-pulse-kpi-label {
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-gray-500);
            }

            .gf-pulse-kpi-value {
                margin-top: 0.25rem;
                font-size: 1.875rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 1.1;
                font-variant-numeric: tabular-nums;
                color: var(--color-gray-950);
            }

            .dark .gf-pulse-kpi-value {
                color: #fff;
            }

            .gf-pulse-kpi-hint {
                margin-top: 0.25rem;
                font-size: 0.75rem;
                color: var(--color-gray-500);
            }

            .gf-pulse-up {
                color: var(--success-600);
            }

            .dark .gf-pulse-up {
                color: var(--success-400);
            }

            .gf-pulse-down {
                color: var(--danger-600);
            }

            .dark .gf-pulse-down {
                color: var(--danger-400);
            }

            .gf-pulse-warn {
                color: var(--warning-600);
            }

            .dark .gf-pulse-warn {
                color: var(--warning-400);
            }

            .gf-pulse-closed {
                margin-bottom: 1rem;
                padding: 0.625rem 0.875rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                background-color: var(--color-gray-100);
                color: var(--color-gray-600);
            }

            .dark .gf-pulse-closed {
                background-color: rgba(255, 255, 255, 0.04);
                color: var(--color-gray-400);
            }

            .gf-pulse-section-title {
                margin-bottom: 0.625rem;
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-gray-500);
            }

            .gf-pulse-split {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.5rem;
                margin-top: 1.5rem;
                padding-top: 1.25rem;
                border-top: 1px solid var(--color-gray-200);
            }

            .dark .gf-pulse-split {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            @media (min-width: 1024px) {
                .gf-pulse-split {
                    grid-template-columns: 3fr 2fr;
                }
            }

            .gf-pulse-chart {
                display: flex;
                align-items: stretch;
                gap: 0.3rem;
            }

            .gf-pulse-day {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                min-width: 0;
            }

            /* El alto vive aquí y no en cada barra: el porcentaje de la barra
               se mide contra este riel, que sí tiene una altura fija. */
            .gf-pulse-day-track {
                display: flex;
                align-items: flex-end;
                height: 4.5rem;
            }

            .gf-pulse-day-bar {
                width: 100%;
                border-radius: 0.25rem 0.25rem 0 0;
                background-color: var(--primary-600);
                min-height: 2px;
                transition: height 150ms ease-out;
            }

            .dark .gf-pulse-day-bar {
                background-color: var(--primary-500);
            }

            .gf-pulse-day--today .gf-pulse-day-bar {
                background-color: var(--primary-400);
            }

            .gf-pulse-day-label {
                font-size: 0.625rem;
                text-align: center;
                color: var(--color-gray-500);
                white-space: nowrap;
            }

            .gf-pulse-day--today .gf-pulse-day-label {
                font-weight: 700;
                color: var(--color-gray-950);
            }

            .dark .gf-pulse-day--today .gf-pulse-day-label {
                color: #fff;
            }

            .gf-pulse-visitors {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .gf-pulse-visitor {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1rem;
                font-size: 0.875rem;
            }

            .gf-pulse-visitor-time {
                flex-shrink: 0;
                font-variant-numeric: tabular-nums;
                color: var(--color-gray-500);
            }

            .gf-pulse-muted {
                font-size: 0.875rem;
                color: var(--color-gray-500);
            }

            .gf-pulse-footer {
                margin-top: 1.25rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--color-gray-200);
                font-size: 0.875rem;
            }

            .dark .gf-pulse-footer {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            .gf-pulse-footer a {
                color: var(--primary-600);
            }

            .dark .gf-pulse-footer a {
                color: var(--primary-400);
            }

            .gf-pulse-footer a:hover {
                text-decoration: underline;
            }
        </style>

        @if ($cerradoHoy)
            <p class="gf-pulse-closed">
                Hoy el gimnasio no abre, así que no se esperan asistencias.
            </p>
        @endif

        <div class="gf-pulse-kpis">
            <div class="gf-pulse-kpi">
                <div class="gf-pulse-kpi-label">Vinieron hoy</div>
                <div class="gf-pulse-kpi-value">{{ $hoy }}</div>
                <div class="gf-pulse-kpi-hint">
                    @if ($cerradoHoy)
                        Día de descanso
                    @elseif ($diferencia > 0)
                        <span class="gf-pulse-up">▲ {{ $diferencia }}</span>
                        vs. {{ $diaAnterior->translatedFormat('l') }}
                    @elseif ($diferencia < 0)
                        <span class="gf-pulse-down">▼ {{ abs($diferencia) }}</span>
                        vs. {{ $diaAnterior->translatedFormat('l') }}
                    @else
                        Igual que el {{ $diaAnterior->translatedFormat('l') }}
                    @endif
                </div>
            </div>

            <div class="gf-pulse-kpi">
                <div class="gf-pulse-kpi-label">Al corriente</div>
                <div class="gf-pulse-kpi-value">{{ $alCorriente }}</div>
                <div class="gf-pulse-kpi-hint">
                    {{ $alCorriente === 1 ? 'cliente con membresía vigente' : 'clientes con membresía vigente' }}
                </div>
            </div>

            <div class="gf-pulse-kpi">
                <div class="gf-pulse-kpi-label">Faltan por venir</div>
                <div class="gf-pulse-kpi-value">{{ $porVenir }}</div>
                <div class="gf-pulse-kpi-hint">de los que están al corriente</div>
            </div>

            <div class="gf-pulse-kpi">
                <div class="gf-pulse-kpi-label">Sin asistir</div>
                <div class="gf-pulse-kpi-value @if ($ausentes > 0) gf-pulse-warn @endif">{{ $ausentes }}</div>
                <div class="gf-pulse-kpi-hint">
                    {{ $ausentes === 1 ? 'lleva días sin venir' : 'llevan días sin venir' }}
                </div>
            </div>
        </div>

        <div class="gf-pulse-split">
            <div>
                <div class="gf-pulse-section-title">Últimos días abiertos</div>

                <div class="gf-pulse-chart">
                    @foreach ($dias as $dia)
                        @php
                            $esHoy = $dia['fecha']->isToday();
                            $alto = ($dia['personas'] / $maxDia) * 100;
                        @endphp

                        <div
                            @class(['gf-pulse-day', 'gf-pulse-day--today' => $esHoy])
                            title="{{ $dia['fecha']->translatedFormat('D d/m') }}: {{ $dia['personas'] }} {{ $dia['personas'] === 1 ? 'persona' : 'personas' }}"
                        >
                            <div class="gf-pulse-day-track">
                                <div class="gf-pulse-day-bar" style="height: {{ $alto }}%"></div>
                            </div>
                            <div class="gf-pulse-day-label">{{ $dia['fecha']->translatedFormat('d') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <div class="gf-pulse-section-title">Quién vino hoy</div>

                @if ($visitantes->isEmpty())
                    <p class="gf-pulse-muted">
                        @if ($cerradoHoy)
                            El gimnasio está cerrado.
                        @else
                            Todavía no llega nadie.
                        @endif
                    </p>
                @else
                    <div class="gf-pulse-visitors">
                        @foreach ($visitantes as $visita)
                            <div class="gf-pulse-visitor">
                                <span>{{ $visita->member->full_name }}</span>
                                <span class="gf-pulse-visitor-time">
                                    {{ $visita->checked_in_at->format('H:i:s') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @if ($hoy > $visitantes->count())
                        <p class="gf-pulse-muted" style="margin-top: 0.5rem;">
                            y {{ $hoy - $visitantes->count() }} más
                        </p>
                    @endif
                @endif
            </div>
        </div>

        <div class="gf-pulse-footer">
            <a href="{{ $clientesUrl }}">Ver clientes y registrar asistencia</a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
