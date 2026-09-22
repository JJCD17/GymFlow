{{--
    Pantalla para un gimnasio que quedó fuera. No es un muro de pago: explica
    qué pasó, no bloquea con urgencia ni pide tarjeta. El dueño no siempre es
    quien contrata, así que el texto apunta a "quien lleva la administración"
    y siempre deja salir con cerrar sesión.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} · GymFlow</title>
    <style>
        :root {
            --fondo: #f9fafb;
            --tarjeta: #fff;
            --borde: #e5e7eb;
            --texto: #111827;
            --suave: #6b7280;
            --acento: #0891b2;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --fondo: #0c0a09;
                --tarjeta: #1c1917;
                --borde: rgba(255, 255, 255, 0.1);
                --texto: #fafaf9;
                --suave: #a8a29e;
                --acento: #22d3ee;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-color: var(--fondo);
            color: var(--texto);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        .marca {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
            color: var(--texto);
        }

        .marca span {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .tarjeta {
            width: 100%;
            max-width: 30rem;
            padding: 2rem;
            border: 1px solid var(--borde);
            border-radius: 0.75rem;
            background-color: var(--tarjeta);
            text-align: center;
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        p {
            margin: 0 0 0.75rem;
            color: var(--suave);
            font-size: 0.9375rem;
        }

        p:last-of-type { margin-bottom: 0; }

        .dato {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--borde);
            font-size: 0.875rem;
            color: var(--suave);
        }

        .salir {
            margin-top: 1.5rem;
        }

        .salir button {
            border: 0;
            background: none;
            padding: 0;
            font: inherit;
            font-size: 0.875rem;
            color: var(--acento);
            cursor: pointer;
        }

        .salir button:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div>
        <div class="marca">
            <svg style="width:1.5rem; height:1.5rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11" />
            </svg>
            <span>GymFlow</span>
        </div>

        <div class="tarjeta">
            <h1>{{ $titulo }}</h1>

            @foreach ($mensajes as $mensaje)
                <p>{{ $mensaje }}</p>
            @endforeach

            @if ($vencioEl)
                <div class="dato">Venció el {{ $vencioEl }}.</div>
            @endif

            <div class="salir">
                <form method="POST" action="{{ $salirUrl }}">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
