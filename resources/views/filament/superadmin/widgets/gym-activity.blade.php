{{--
    Estilos en CSS plano con las variables del panel: las utilidades de Tailwind
    no llegan a las vistas de Filament. El acento es --primary-*, sin "color".
--}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Actividad de los gimnasios</x-slot>

        <x-slot name="description">
            Quién usa GymFlow de verdad. Solo cuenta gimnasios activos: los suspendidos o vencidos no pueden entrar.
        </x-slot>

        <style>
            .gf-act-kpis {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
                gap: 1rem;
            }

            .gf-act-kpi {
                padding: 0.875rem 1rem;
                border: 1px solid var(--color-gray-200);
                border-radius: 0.75rem;
                background-color: var(--color-gray-50);
            }

            .dark .gf-act-kpi {
                border-color: rgba(255, 255, 255, 0.1);
                background-color: rgba(255, 255, 255, 0.02);
            }

            .gf-act-kpi-label {
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-gray-500);
            }

            .gf-act-kpi-value {
                margin-top: 0.25rem;
                font-size: 1.875rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                line-height: 1.1;
                font-variant-numeric: tabular-nums;
                color: var(--color-gray-950);
            }

            .dark .gf-act-kpi-value {
                color: #fff;
            }

            .gf-act-kpi-value--ok {
                color: var(--success-600);
            }

            .dark .gf-act-kpi-value--ok {
                color: var(--success-400);
            }

            .gf-act-kpi-value--danger {
                color: var(--danger-600);
            }

            .dark .gf-act-kpi-value--danger {
                color: var(--danger-400);
            }

            .gf-act-kpi-value--warning {
                color: var(--warning-600);
            }

            .dark .gf-act-kpi-value--warning {
                color: var(--warning-400);
            }

            .gf-act-kpi-hint {
                margin-top: 0.25rem;
                font-size: 0.75rem;
                color: var(--color-gray-500);
            }

            .gf-act-title {
                margin-top: 1.5rem;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                color: var(--color-gray-950);
            }

            .dark .gf-act-title {
                color: #fff;
            }

            .gf-act-list {
                border: 1px solid var(--color-gray-200);
                border-radius: 0.75rem;
                overflow: hidden;
            }

            .dark .gf-act-list {
                border-color: rgba(255, 255, 255, 0.1);
            }

            .gf-act-row {
                display: block;
                padding: 0.875rem 1rem;
                border-top: 1px solid var(--color-gray-200);
            }

            .gf-act-row:first-child {
                border-top: 0;
            }

            .dark .gf-act-row {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            .gf-act-row:hover {
                background-color: var(--color-gray-50);
            }

            .dark .gf-act-row:hover {
                background-color: rgba(255, 255, 255, 0.03);
            }

            .gf-act-row-head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .gf-act-name {
                font-weight: 600;
                color: var(--color-gray-950);
            }

            .dark .gf-act-name {
                color: #fff;
            }

            .gf-act-badge {
                padding: 0.125rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }

            .gf-act-badge--idle,
            .gf-act-badge--unstarted {
                background-color: var(--danger-50);
                color: var(--danger-700);
            }

            .dark .gf-act-badge--idle,
            .dark .gf-act-badge--unstarted {
                background-color: rgba(255, 255, 255, 0.05);
                color: var(--danger-400);
            }

            .gf-act-badge--low_use {
                background-color: var(--warning-50);
                color: var(--warning-700);
            }

            .dark .gf-act-badge--low_use {
                background-color: rgba(255, 255, 255, 0.05);
                color: var(--warning-400);
            }

            .gf-act-message {
                margin-top: 0.25rem;
                font-size: 0.875rem;
                color: var(--color-gray-700);
            }

            .dark .gf-act-message {
                color: var(--color-gray-300);
            }

            .gf-act-signals {
                display: flex;
                flex-wrap: wrap;
                gap: 0.25rem 1.25rem;
                margin-top: 0.5rem;
                font-size: 0.75rem;
                color: var(--color-gray-500);
            }

            .gf-act-signal strong {
                font-weight: 500;
                color: var(--color-gray-700);
            }

            .dark .gf-act-signal strong {
                color: var(--color-gray-300);
            }

            .gf-act-empty {
                margin-top: 1.25rem;
                padding: 1rem;
                border: 1px dashed var(--color-gray-300);
                border-radius: 0.75rem;
                font-size: 0.875rem;
                text-align: center;
                color: var(--color-gray-500);
            }

            .dark .gf-act-empty {
                border-color: rgba(255, 255, 255, 0.15);
            }
        </style>

        @php
            $etiquetas = [
                \App\Support\GymActivity::STATUS_UNSTARTED => 'Sin estrenar',
                \App\Support\GymActivity::STATUS_IDLE => 'Sin uso reciente',
                \App\Support\GymActivity::STATUS_LOW_USE => 'Poco uso',
            ];
        @endphp

        <div class="gf-act-kpis">
            <div class="gf-act-kpi">
                <div class="gf-act-kpi-label">Al día</div>
                <div class="gf-act-kpi-value gf-act-kpi-value--ok">{{ $alDia }}</div>
                <div class="gf-act-kpi-hint">de {{ $total }} activos</div>
            </div>

            <div class="gf-act-kpi">
                <div class="gf-act-kpi-label">Sin uso reciente</div>
                <div class="gf-act-kpi-value @if ($sinUso > 0) gf-act-kpi-value--danger @endif">{{ $sinUso }}</div>
                <div class="gf-act-kpi-hint">{{ $diasSinUso }} días o más sin entrar</div>
            </div>

            <div class="gf-act-kpi">
                <div class="gf-act-kpi-label">Sin estrenar</div>
                <div class="gf-act-kpi-value @if ($sinEstrenar > 0) gf-act-kpi-value--danger @endif">{{ $sinEstrenar }}</div>
                <div class="gf-act-kpi-hint">nunca lo han usado</div>
            </div>

            <div class="gf-act-kpi">
                <div class="gf-act-kpi-label">Poco uso</div>
                <div class="gf-act-kpi-value @if ($pocoUso > 0) gf-act-kpi-value--warning @endif">{{ $pocoUso }}</div>
                <div class="gf-act-kpi-hint">casi no registran</div>
            </div>
        </div>

        @if ($atencion->isEmpty())
            <div class="gf-act-empty">
                @if ($total === 0)
                    No hay gimnasios activos todavía.
                @else
                    Todos los gimnasios activos están usando GymFlow.
                @endif
            </div>
        @else
            <div class="gf-act-title">Necesitan atención</div>

            <div class="gf-act-list">
                @foreach ($atencion as $row)
                    <a href="{{ $row['url'] }}" class="gf-act-row">
                        <div class="gf-act-row-head">
                            <span class="gf-act-name">{{ $row['gym']->name }}</span>
                            <span class="gf-act-badge gf-act-badge--{{ $row['status'] }}">{{ $etiquetas[$row['status']] }}</span>
                        </div>

                        <div class="gf-act-message">{{ $row['message'] }}</div>

                        <div class="gf-act-signals">
                            @foreach ($row['signals'] as $label => $fecha)
                                <span class="gf-act-signal" @if ($fecha) title="{{ $fecha->translatedFormat('d \d\e F \d\e Y, H:i') }}" @endif>
                                    {{ $label }}: <strong>{{ $fecha ? $fecha->diffForHumans() : 'nunca' }}</strong>
                                </span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
