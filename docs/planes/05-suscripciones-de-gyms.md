# Plan 05 — Suscripciones de gimnasios (panel de superadmin)

**Estado:** En progreso
**Objetivo:** Que el superadmin registre qué plan de GymFlow tiene contratado cada gimnasio (semanal, mensual, semestral, anual, prueba...), desde cuándo y hasta cuándo, y que el sistema apague el acceso solo cuando ese plan vence — sin dejar de poder suspender un gym a mano por otro motivo.

## El hueco que cierra

Hoy `Gym.is_active` es un solo interruptor manual (Suspender/Reactivar en el listado de gimnasios). No hay ningún registro de qué le vendimos a cada gym ni de cuándo vence: si mañana un gym no paga, nadie lo sabe hasta que alguien se acuerda de revisarlo a mano. Esto añade el catálogo de planes de GymFlow y la suscripción vigente de cada gym, siguiendo el mismo patrón que ya existe para los planes que un gym vende a sus clientes (`Plan` + `Membership`), pero un nivel arriba: del superadmin hacia el gym.

## Nomenclatura

Para no chocar con `Plan`/`Membership` (que son del gym hacia sus clientes):

- **`SubscriptionPlan`** — catálogo del superadmin: Semanal, Mensual, Semestral, Anual, Prueba, etc. Campos: `name`, `duration_days`, `price`, `is_active`, `sort_order`.
- **`GymSubscription`** — qué plan tiene cada gym: `gym_id`, `subscription_plan_id`, `starts_at`, `ends_at`, `status` (`active` | `cancelled`), `notes`.

Mismo criterio que `Membership`: `status` no lo mantiene un cron, se deriva de `ends_at` vía scopes/accessors (`isExpired`, `scopeActive`, `scopeExpired`) — `status` solo distingue una cancelación explícita de una que simplemente venció.

## `is_active` del gym: dos causas, un resultado

`Gym.is_active` deja de ser un campo capturado a mano y pasa a ser una columna calculada por dos señales independientes:

1. **Suspensión manual** (la que ya existe) — un nuevo campo `suspended_at` (nullable) sustituye la semántica actual de `is_active`. El botón Suspender/Reactivar del listado escribe/borra esta fecha, igual que hoy pero con otro nombre de columna.
2. **Vencimiento de la suscripción** — si la `GymSubscription` vigente del gym ya pasó su `ends_at` y no hay una renovación activa, el gym tampoco debería poder entrar.

`is_active` final = `suspended_at is null` **y** `gymSubscription activa vigente`. Como `canAccessPanel()` en `User` es lo único que hoy lee `Gym.is_active` para bloquear el acceso, ahí es donde se combina — no hace falta una columna física adicional ni un job que la reescriba; se calcula al vuelo igual que `Membership::isExpired`.

El toggle "Suspender/Reactivar" sigue tal cual en la tabla de gimnasios, solo que ahora actúa sobre `suspended_at` en vez de `is_active`, y la razón de estar inactivo (suspendido vs. vencido) se distingue en pantalla para que el superadmin sepa cuál de las dos es.

## Duración del plan

Igual que `Plan.duration_days` del gym: un número de días configurable en el catálogo, con el mismo helper de "equivale a X meses/años" que ya existe en `PlanForm` (se reutiliza el criterio, no hace falta reinventarlo).

## Pantallas (panel de superadmin, `App\Filament\Superadmin\Resources\...`)

- **Catálogo de planes de suscripción** — recurso nuevo `SubscriptionPlanResource`, calcado de `PlanResource`/`PlanForm`/`PlansTable`: nombre, precio, duración en días con el hint de equivalencia, activo/inactivo, orden por arrastrar, borrado bloqueado si ya tiene gyms suscritos.
- **Asignar/renovar suscripción de un gym** — no es un recurso aparte con su propio menú; vive dentro de `EditGym` (o una relación en el propio listado de gimnasios), mostrando la suscripción vigente y permitiendo registrar una nueva (elige `SubscriptionPlan`, arranca hoy o una fecha, `ends_at` se calcula solo desde `duration_days`, editable si hace falta un ajuste).
- **Listado de gimnasios** — la columna de estado distingue "Suspendido" de "Vencido" de "Activo hasta {fecha}", y se agrega un filtro para ver rápido quién vence pronto o ya venció.

## Fuera de alcance de esta fase

- Cobro automático / pasarela de pago (Stripe u otra). Esto queda para una fase aparte si se decide más adelante.
- Notificaciones automáticas (correo/WhatsApp) de vencimiento próximo — se puede añadir después con el mismo criterio que `Gym.message_expiring` usa para los clientes del gym.

## Checklist end-to-end

- [x] Migración `subscription_plans` (nombre, precio, duración, prueba, activo, orden)
- [x] Migración `gym_subscriptions` (gym, plan, fechas, status, notas)
- [x] Migración: `Gym.is_active` → `Gym.suspended_at` (nullable, timestamp)
- [x] Modelo `SubscriptionPlan` (mismo patrón que `Plan`: scope activo, `isDeletable()`, `sort_order` automático)
- [x] Modelo `GymSubscription` (mismo patrón que `Membership`: cálculo de `ends_at`, `isExpired`, `scopeActive`/`scopeExpired`)
- [x] `Gym::hasActiveSubscription()` y accessor `is_active` calculado (suspensión manual + vigencia de suscripción)
- [x] Scopes `active`/`inactive`/`suspended`/`expiringWithin` en `Gym`
- [x] `User::canAccessPanel()` usa el nuevo cálculo
- [x] El toggle Suspender/Reactivar de `GymsTable` escribe `suspended_at`
- [x] Recurso `SubscriptionPlanResource` en el panel de superadmin (catálogo)
- [x] Alta/renovación de `GymSubscription` desde la pantalla del gym (relation manager)
- [x] El alta de un gimnasio pide su suscripción inicial
- [x] Columna de estado en el listado distinguiendo suspendido/vencido/activo
- [x] Filtro por estado y de "vencen en 15 días" en el listado
- [x] El escritorio del superadmin cuenta activos, inactivos y por vencer
- [x] Catálogo sembrado (`SubscriptionPlanTemplates` + seeder)
- [x] `docs/info/suscripciones-de-gyms.md` con la regla de `is_active` calculado
- [x] Pruebas: vencido y suspendido quedan fuera; reactivar y renovar restauran el acceso (13 casos)
- [x] La sesión ya abierta se corta al vencer o suspender, sin esperar a que cierre sesión
- [x] Pantalla que explica el motivo en vez de un 403, distinguiendo pausado de vencido
- [x] Cerrar sesión funciona desde esa pantalla (no queda atrapado)
- [ ] Verificación en el navegador por el usuario
