# Modelo de datos — cómo funciona

Documentación de las tablas, modelos y mecanismos automáticos de GymFlow.

## Multi-tenancy: cómo se aísla la información de cada gimnasio

Todas las tablas de negocio (`plans`, `members`, `memberships`, `payments`, `check_ins`) tienen una columna `gym_id`. El aislamiento **no depende de que el programador se acuerde de filtrar**: está centralizado en el trait `App\Models\Concerns\BelongsToGym`.

Ese trait hace dos cosas automáticamente en todos los modelos que lo usan:

1. **Filtra al leer** (Global Scope): cualquier consulta (`Member::all()`, `Payment::count()`, etc.) agrega solo `WHERE gym_id = <gimnasio del usuario logueado>` sin que haya que escribirlo.
2. **Rellena al escribir**: al crear un registro, le pone el `gym_id` del usuario logueado solo. Por eso en el código se escribe `Member::create(['full_name' => 'Juan'])` sin mencionar el gimnasio.

**Excepción del super-admin:** si el usuario tiene `role = super_admin`, el filtro se desactiva y ve todos los registros de todos los gimnasios. Esto es intencional, para que pueda administrar el sistema completo.

**Importante:** el filtro depende del usuario autenticado. En comandos de consola, jobs o seeders sin sesión iniciada, no hay filtro y se ven todos los registros — ahí hay que filtrar explícitamente por `gym_id`.

## Cálculos automáticos (principio "puro clic")

Estos valores **nunca se capturan a mano**, el sistema los deriva:

| Dato | Cómo se obtiene |
|---|---|
| `memberships.starts_at` | Si no se indica, es la fecha de hoy |
| `memberships.ends_at` | `starts_at` + los `duration_days` del plan elegido |
| `days_remaining` | Días entre hoy y `ends_at` (negativo si ya venció) |
| `is_expired` | Verdadero si `ends_at` ya pasó y no está cancelada |

Al inscribir un cliente basta con elegir **el plan**; las fechas salen solas.

## Scopes disponibles para alertas y dashboard

Consultas ya listas para los widgets, en vez de repetir la lógica en cada pantalla:

- `Membership::active()` — membresías vigentes
- `Membership::expired()` — vencidas (para el widget de "vencidos sin renovar")
- `Membership::expiringWithin(7)` — por vencer en los próximos N días
- `CheckIn::today()` — asistencias del día
- `Plan::active()` / `Member::active()` — solo registros activos

## Planes de plantilla al crear un gimnasio

La acción `App\Actions\CreateGymWithOwner` hace tres cosas en una sola transacción (si algo falla, no queda nada a medias):

1. Crea el gimnasio, generando su `code` único a partir del nombre (si ya existe, le agrega `-2`, `-3`, etc.)
2. Crea el usuario dueño con `role = owner` ligado a ese gimnasio
3. Siembra los planes de plantilla definidos en `App\Support\PlanTemplates`

Los precios y nombres de esas plantillas son solo un punto de partida: cada dueño los edita, desactiva, borra o amplía desde su panel.

## Decisiones de diseño y su razón

- **`full_name` en un solo campo** en vez de nombre/apellido separados: menos fricción al capturar y evita ambigüedad con nombres y apellidos compuestos, muy común en México. No hay ningún requerimiento del negocio que necesite el apellido por separado.
- **Sin fecha de nacimiento:** no aporta a la operación diaria y es un campo manual más. Si en el futuro se quiere felicitar cumpleaños, se agrega entonces.
- **Campo `code` en `gyms`:** es un identificador amigable derivado del nombre (ej. `gimnasio-el-fuerte`). Hoy no se usa en URLs, se dejó preparado por si cada gimnasio llega a tener una página pública propia.
- **Renovaciones = registros nuevos:** al renovar no se edita la membresía anterior, se crea otra. Así queda el historial completo del cliente sin perder información.
- **`payments.membership_id` es opcional:** permite registrar un pago suelto que no corresponde a una membresía (por ejemplo, venta de un producto o penalización).
- **Nombres de tablas y columnas en inglés:** es la convención de Laravel y de los paquetes del ecosistema. Toda la interfaz que ve el usuario final está en español.
- **`gyms.timezone` se captura pero todavía no se usa:** se pide al dar de alta el gimnasio, pero ninguna pantalla la aplica aún. Importa cuando el sistema decida qué es "hoy": si un gimnasio está en Tijuana y el servidor en horario del centro, una asistencia de las 11 PM se guardaría con la fecha del día siguiente, y el cliente aparecería como ausente cuando sí fue. Lo mismo afecta el corte de ingresos del día. **Pendiente:** aplicar la zona horaria del gimnasio al mostrar y agrupar fechas en asistencias, pagos y alertas.

## Métodos de pago

Definidos en `Payment::METHODS` como una lista fija: efectivo, tarjeta, transferencia. Se eligen de un desplegable, no se escriben.

**Mejora futura:** si algún gimnasio necesita métodos propios, habría que moverlos a una tabla con `gym_id`. Por ahora la lista fija cubre el caso real y es más simple.

## Mejoras futuras identificadas

- **Recalcular el estado de membresías:** hoy `is_expired` se calcula al momento de consultar, y `status` se queda como se guardó. Falta un job diario que marque como `expired` las que ya vencieron, para que los listados filtrados por `status` sean exactos sin depender del cálculo en vivo.
- **Factories para pruebas automatizadas:** aún no se crean; pendientes para poder escribir tests.
- **Usuario en varios gimnasios:** hoy un usuario pertenece a un solo gimnasio (`gym_id` directo). Si alguien llegara a administrar varias sucursales, habría que pasar a una tabla intermedia.
- **Contacto rápido:** pendiente definir el mecanismo (la opción más simple es un enlace `wa.me` con el mensaje ya escrito, sin integrar ninguna API).
- **Borrado de gimnasios:** actualmente al borrar un gimnasio se borra en cascada todo su contenido. Para producción conviene preferir la desactivación (`is_active = false`) sobre el borrado real.
