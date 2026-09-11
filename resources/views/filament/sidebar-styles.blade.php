<style>
    /* Filament le fija una altura al contenedor del logo; el contenido propio
       (ícono + texto) mide más y se desbordaba sobre el formulario. */
    .fi-logo {
        height: auto !important;
        display: flex;
        align-items: center;
    }

    /* En el login la marca se muestra aparte, en grande, vía render hook. */
    .fi-simple-layout .fi-logo {
        display: none;
    }

    .fi-sidebar-nav {
        border-right: 1px solid var(--color-gray-200);
        background-color: var(--color-gray-50);
    }

    .dark .fi-sidebar-nav {
        border-right-color: rgba(255, 255, 255, 0.08);
        background-color: rgba(255, 255, 255, 0.02);
    }

    .fi-sidebar-group-label {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        opacity: 0.6;
    }

    .fi-sidebar-group + .fi-sidebar-group {
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--color-gray-200);
    }

    .dark .fi-sidebar-group + .fi-sidebar-group {
        border-top-color: rgba(255, 255, 255, 0.06);
    }
</style>
