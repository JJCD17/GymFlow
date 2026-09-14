{{--
    Los estilos van en CSS plano con las variables de Filament, no en clases de
    Tailwind: el panel usa su propio CSS compilado y las utilidades de la app no
    llegan hasta aquí. Mismo criterio que en filament/sidebar-styles.
--}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Planes más vendidos</x-slot>

        <x-slot name="description">
            Qué se está vendiendo y cuánto deja cada plan.
        </x-slot>

        <x-slot name="afterHeader">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="range">
                    @foreach ($ranges as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        <style>
            .gf-plan-empty {
                padding: 1.5rem 0;
                text-align: center;
                color: var(--color-gray-500);
                font-size: 0.875rem;
            }

            .gf-plan-totals {
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                gap: 0.5rem 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid var(--color-gray-200);
            }

            .dark .gf-plan-totals {
                border-bottom-color: rgba(255, 255, 255, 0.1);
            }

            .gf-plan-total-value {
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: -0.02em;
            }

            .gf-plan-total-label {
                margin-inline-start: 0.25rem;
                font-size: 0.875rem;
                color: var(--color-gray-500);
            }

            .gf-plan-rows {
                margin-top: 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .gf-plan-row--empty {
                opacity: 0.55;
            }

            .gf-plan-line {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1rem;
                font-size: 0.875rem;
            }

            .gf-plan-name {
                font-weight: 500;
            }

            .gf-plan-off {
                margin-inline-start: 0.25rem;
                font-size: 0.75rem;
                font-weight: 400;
                color: var(--color-gray-500);
            }

            .gf-plan-figures {
                flex-shrink: 0;
                font-variant-numeric: tabular-nums;
                color: var(--color-gray-500);
            }

            .gf-plan-money {
                font-weight: 600;
                color: var(--color-gray-950);
            }

            .dark .gf-plan-money {
                color: #fff;
            }

            .gf-plan-count {
                margin-inline-start: 0.5rem;
            }

            .gf-plan-track {
                margin-top: 0.375rem;
                height: 0.5rem;
                border-radius: 9999px;
                background-color: var(--color-gray-100);
                overflow: hidden;
            }

            .dark .gf-plan-track {
                background-color: rgba(255, 255, 255, 0.06);
            }

            /* La paleta del panel expone --primary-*, sin el prefijo "color".
               Los grises existen en ambas formas, primary solo en esta. */
            .gf-plan-bar {
                height: 100%;
                border-radius: 9999px;
                background-color: var(--primary-600);
                transition: width 150ms ease-out;
            }

            .dark .gf-plan-bar {
                background-color: var(--primary-500);
            }

            .gf-plan-footer {
                margin-top: 1rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--color-gray-200);
                font-size: 0.875rem;
            }

            .dark .gf-plan-footer {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            .gf-plan-footer a {
                color: var(--primary-600);
            }

            .dark .gf-plan-footer a {
                color: var(--primary-400);
            }

            .gf-plan-footer a:hover {
                text-decoration: underline;
            }
        </style>

        @if ($ventasTotales === 0)
            <p class="gf-plan-empty">
                No se vendió ninguna membresía en este periodo.<br>
                Prueba con un rango más amplio.
            </p>
        @else
            <div class="gf-plan-totals">
                <div>
                    <span class="gf-plan-total-value">${{ number_format($ingresosTotales, 2) }}</span>
                    <span class="gf-plan-total-label">cobrado</span>
                </div>
                <div>
                    <span class="gf-plan-total-value">{{ $ventasTotales }}</span>
                    <span class="gf-plan-total-label">
                        {{ $ventasTotales === 1 ? 'membresía vendida' : 'membresías vendidas' }}
                    </span>
                </div>
            </div>

            <div class="gf-plan-rows">
                @foreach ($filas as $fila)
                    @php
                        $plan = $fila['plan'];
                        $ancho = $maxVentas > 0 ? ($fila['ventas'] / $maxVentas) * 100 : 0;
                        $sinVentas = $fila['ventas'] === 0;
                    @endphp

                    <div @class(['gf-plan-row--empty' => $sinVentas])>
                        <div class="gf-plan-line">
                            <span class="gf-plan-name">
                                {{ $plan->name }}

                                @unless ($plan->is_active)
                                    <span class="gf-plan-off">(desactivado)</span>
                                @endunless
                            </span>

                            <span class="gf-plan-figures">
                                <span class="gf-plan-money">${{ number_format($fila['ingresos'], 2) }}</span>
                                <span class="gf-plan-count">
                                    {{ $fila['ventas'] }} {{ $fila['ventas'] === 1 ? 'venta' : 'ventas' }}
                                </span>
                            </span>
                        </div>

                        @unless ($sinVentas)
                            <div class="gf-plan-track">
                                {{-- Un mínimo visible: con muchas ventas del
                                     puntero, una sola venta se vería como nada. --}}
                                <div class="gf-plan-bar" style="width: {{ max($ancho, 2) }}%"></div>
                            </div>
                        @endunless
                    </div>
                @endforeach
            </div>

            <div class="gf-plan-footer">
                <a href="{{ $planesUrl }}">Ajustar mis planes</a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
