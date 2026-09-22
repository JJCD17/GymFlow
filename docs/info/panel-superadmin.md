# Panel de superadmin — Inicio

## Gimnasios (KPIs)

Primer tablero del panel de superadmin (`/superadmin`). Responde lo primero que se pregunta un superadmin al entrar: cuántos gimnasios hay en GymFlow y cuántos están operando.

- **Total de gimnasios** — todos los registros de `gyms`, sin filtrar.
- **Activos** / **Inactivos** — según `Gym::is_active`. No hay soft deletes ni tabla de suscripción: un gimnasio "inactivo" es uno suspendido desde este mismo panel (ver [[paneles-y-acceso]], sección "Suspender un gimnasio"), no uno eliminado.

Consulta directa en el widget (`App\Filament\Superadmin\Widgets\GymsOverviewWidget`), sin clase de soporte: son dos `count()` sobre `Gym`, no amerita una capa extra como `GymPulse` o `PlanSales`.

Mismos estilos en CSS plano que el resto de tableros (ver [[escritorio]]).
