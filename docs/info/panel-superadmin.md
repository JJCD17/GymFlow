# Panel de superadmin — Inicio

## Gimnasios (KPIs)

Primer tablero del panel de superadmin (`/superadmin`). Responde lo primero que se pregunta un superadmin al entrar: cuántos gimnasios hay en GymFlow y cuántos están operando.

- **Total de gimnasios** — todos los registros de `gyms`, sin filtrar.
- **Activos** / **Inactivos** — según `Gym::is_active`. No hay soft deletes ni tabla de suscripción: un gimnasio "inactivo" es uno suspendido desde este mismo panel (ver [[paneles-y-acceso]], sección "Suspender un gimnasio"), no uno eliminado.

Consulta directa en el widget (`App\Filament\Superadmin\Widgets\GymsOverviewWidget`), sin clase de soporte: son dos `count()` sobre `Gym`, no amerita una capa extra como `GymPulse` o `PlanSales`.

Mismos estilos en CSS plano que el resto de tableros (ver [[escritorio]]).

## Actividad de los gimnasios

Segundo tablero de Inicio. Estar activo solo dice que un gimnasio *puede* entrar; este dice si de verdad usa GymFlow. Plan: `docs/planes/06-actividad-de-gyms.md`.

**Señales**, por gimnasio: login del dueño (`users.last_login_at`), última interacción de cualquier usuario del gym (`users.last_seen_at`), último cliente (`members.created_at`), última asistencia (`check_ins.checked_in_at`) y último pago (`payments.paid_at`). La última actividad es la más reciente de las cinco.

- `last_login_at` lo escribe `App\Listeners\RecordLogin` con el evento `Login` (se descubre solo, sin registrarlo). También se dispara al entrar con "recordarme".
- `last_seen_at` lo escribe `App\Http\Middleware\RecordLastSeen`, solo en el panel del gimnasio y como mucho cada 5 minutos. Ninguno de los dos toca `updated_at`.

**Clasificación** (`App\Support\GymActivity`, umbrales en constantes). Solo evalúa gimnasios activos: uno suspendido o vencido no puede entrar, su inactividad no dice nada.

| Estado | Regla |
| --- | --- |
| Sin estrenar | Ninguna señal y registrado hace 3 días o más |
| Sin uso reciente | Última actividad hace 7 días o más |
| Poco uso | 14 días o más registrado, menos de 5 clientes y menos de 10 asistencias en 14 días |
| Al día | El resto, incluidos los recién registrados |

El widget lista solo los que necesitan atención, del que más urge al que menos (dentro de cada estado, el que lleva más días sin uso va primero). Cada fila lleva a la pantalla del gimnasio.

Las fechas salen de subconsultas `withMax` sobre las relaciones de `Gym`. El superadmin no tiene `gym_id`, así que el scope global de `BelongsToGym` no filtra nada.
