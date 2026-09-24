# Plan 06 — Actividad de los gimnasios (panel de superadmin)

**Estado:** En progreso
**Objetivo:** Que el superadmin vea qué gimnasios **usan** GymFlow y cuáles no, no solo cuáles están activos. Un gym con suscripción vigente que lleva días sin entrar, o que nunca terminó de arrancar, es uno que probablemente no renueve.

## El hueco que cierra

`is_active` dice si un gym *puede* entrar, no si entra. Un gym registrado hace 40 días que casi no captura clientes ni asistencias se ve igual de "activo" que uno que pasa lista todos los días.

## Qué cuenta como actividad

Cinco señales, todas por gimnasio:

| Señal | Fuente |
| --- | --- |
| Último login del dueño | `users.last_login_at` (nuevo) del usuario `owner` |
| Última interacción | `users.last_seen_at` (nuevo), de cualquier usuario del gym |
| Último cliente registrado | `members.created_at` |
| Última asistencia | `check_ins.checked_in_at` |
| Último pago | `payments.paid_at` |

La **última actividad** es la más reciente de las cinco.

- `last_login_at` lo escribe un listener del evento `Login` (también cubre la entrada con "recordarme").
- `last_seen_at` lo escribe un middleware del panel del gimnasio, como mucho una vez cada 5 minutos para no escribir en cada request. No toca `updated_at`.

## Clasificación

Solo gimnasios activos: uno suspendido o vencido no puede entrar, su inactividad no dice nada.

- **Sin estrenar** — no tiene ninguna señal y se registró hace 3 días o más.
- **Sin uso reciente** — su última actividad fue hace 7 días o más.
- **Poco uso** — entra, pero lleva 14 días o más registrado y tiene menos de 5 clientes y menos de 10 asistencias en los últimos 14 días.
- **Al día** — el resto (incluye a los recién registrados, dentro de sus 3 días de gracia).

Los umbrales son constantes en `App\Support\GymActivity`.

## Pantalla

Widget en Inicio del superadmin, debajo de los KPIs de gimnasios:

- Cuatro conteos: al día, sin uso reciente, sin estrenar, poco uso
- Lista de los que necesitan atención, el más abandonado primero, con su mensaje ("Lleva 12 días sin utilizar GymFlow") y las cinco fechas
- Cada fila lleva a la pantalla del gimnasio

## Fuera de alcance

- Columna de "última actividad" en el listado de gimnasios (se puede reutilizar `GymActivity` después)
- Avisos automáticos al dueño o al superadmin

## Checklist end-to-end

- [x] Columnas `last_login_at` y `last_seen_at` en `users`
- [x] Listener que guarda el login
- [x] Middleware que guarda la última interacción en el panel del gimnasio
- [x] `GymActivity` con las señales y la clasificación
- [x] Widget en Inicio del superadmin
- [x] Pruebas de clasificación y registro de login/interacción
- [x] `docs/info/panel-superadmin.md` actualizado
- [ ] Verificación en el navegador por el usuario
