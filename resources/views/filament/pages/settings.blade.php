{{-- Estilos en CSS plano: las utilidades de Tailwind no llegan al panel. --}}
<x-filament-panels::page>
    <style>
        .gf-wa-preview-label {
            margin-bottom: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--color-gray-500);
        }

        /* Burbuja de mensaje enviado, con los colores de WhatsApp. */
        .gf-wa-bubble {
            display: inline-block;
            max-width: 100%;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem 0 0.5rem 0.5rem;
            background-color: #d9fdd3;
            color: #111b21;
            font-size: 0.875rem;
            line-height: 1.4;
            box-shadow: 0 1px 0.5px rgba(11, 20, 26, 0.13);
        }

        .dark .gf-wa-bubble {
            background-color: #005c4b;
            color: #e9edef;
        }
    </style>

    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit">
                Guardar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
