{{--
    Estilos en CSS plano con las variables del panel: las utilidades de Tailwind
    no llegan a las vistas de Filament. El acento es --primary-*, sin "color".
--}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Gimnasios</x-slot>

        <x-slot name="description">
            Panorama general de los gimnasios registrados en GymFlow.
        </x-slot>

        <style>
            .gf-gyms-kpis {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
                gap: 1rem;
            }

            .gf-gyms-kpi {
                padding: 0.875rem 1rem;
                border: 1px solid var(--color-gray-200);
                border-radius: 0.75rem;
                background-color: var(--color-gray-50);
            }

            .dark .gf-gyms-kpi {
                border-color: rgba(255, 255, 255, 0.1);
                background-color: rgba(255, 255, 255, 0.02);
            }

            .gf-gyms-kpi-label {
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-gray-500);
            }

            .gf-gyms-kpi-value {
                margin-top: 0.25rem;
                font-size: 1.875rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 1.1;
                font-variant-numeric: tabular-nums;
                color: var(--color-gray-950);
            }

            .dark .gf-gyms-kpi-value {
                color: #fff;
            }

            .gf-gyms-kpi-value--activos {
                color: var(--success-600);
            }

            .dark .gf-gyms-kpi-value--activos {
                color: var(--success-400);
            }

            .gf-gyms-kpi-value--inactivos {
                color: var(--danger-600);
            }

            .dark .gf-gyms-kpi-value--inactivos {
                color: var(--danger-400);
            }

            .gf-gyms-kpi-value--aviso {
                color: var(--warning-600);
            }

            .dark .gf-gyms-kpi-value--aviso {
                color: var(--warning-400);
            }

            .gf-gyms-kpi-hint {
                margin-top: 0.25rem;
                font-size: 0.75rem;
                color: var(--color-gray-500);
            }

            .gf-gyms-footer {
                margin-top: 1.25rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--color-gray-200);
                font-size: 0.875rem;
            }

            .dark .gf-gyms-footer {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            .gf-gyms-footer a {
                color: var(--primary-600);
            }

            .dark .gf-gyms-footer a {
                color: var(--primary-400);
            }

            .gf-gyms-footer a:hover {
                text-decoration: underline;
            }
        </style>

        <div class="gf-gyms-kpis">
            <div class="gf-gyms-kpi">
                <div class="gf-gyms-kpi-label">Total de gimnasios</div>
                <div class="gf-gyms-kpi-value">{{ $total }}</div>
            </div>

            <div class="gf-gyms-kpi">
                <div class="gf-gyms-kpi-label">Activos</div>
                <div class="gf-gyms-kpi-value gf-gyms-kpi-value--activos">{{ $activos }}</div>
                <div class="gf-gyms-kpi-hint">con suscripción vigente</div>
            </div>

            <div class="gf-gyms-kpi">
                <div class="gf-gyms-kpi-label">Inactivos</div>
                <div class="gf-gyms-kpi-value gf-gyms-kpi-value--inactivos">{{ $inactivos }}</div>
                <div class="gf-gyms-kpi-hint">
                    @if ($suspendidos > 0)
                        {{ $suspendidos }} {{ $suspendidos === 1 ? 'suspendido' : 'suspendidos' }},
                        {{ $inactivos - $suspendidos }} por vencimiento
                    @else
                        por vencimiento
                    @endif
                </div>
            </div>

            <div class="gf-gyms-kpi">
                <div class="gf-gyms-kpi-label">Vencen pronto</div>
                <div class="gf-gyms-kpi-value @if ($porVencer > 0) gf-gyms-kpi-value--aviso @endif">{{ $porVencer }}</div>
                <div class="gf-gyms-kpi-hint">en los próximos 15 días</div>
            </div>
        </div>

        <div class="gf-gyms-footer">
            <a href="{{ $gimnasiosUrl }}">Ver gimnasios</a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
