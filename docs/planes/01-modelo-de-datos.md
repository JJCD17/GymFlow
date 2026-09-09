# Plan 01 — Modelo de datos base (multi-tenant)

**Estado:** En progreso
**Objetivo:** Definir y construir el esquema de base de datos que soporta gimnasios, usuarios, planes de membresía, membresías de clientes, pagos, asistencias, alertas y el dashboard principal — todo aislado por gimnasio (multi-tenant) y pensado para una operación "todo a clics".

## Decisiones de arquitectura

- **Multi-tenancy:** una sola base de datos. Todas las tablas de negocio llevan `gym_id` y se filtran automáticamente con un Global Scope de Eloquent + el sistema de tenancy nativo de Filament (`->tenant()`). Ningún query de negocio debe poder cruzar datos entre gimnasios.
- **Alta de gimnasios:** los crea un **super-admin** desde un panel separado (`/super-admin` o panel central sin tenant). No hay registro público por ahora.
- **Planes de membresía:** son **por gimnasio** (`gym_id` en la tabla `plans`), pero al crear un gimnasio nuevo se siembran automáticamente planes de plantilla (Mensual, Trimestral, Semestral, Anual, Visita por día) que el dueño puede editar, activar/desactivar, borrar o ampliar — sin partir de una pantalla vacía.
- **UX "puro clic":** todo lo que se pueda derivar (estado de membresía, días restantes, adeudo, alertas de vencimiento/inasistencia) se calcula por el sistema, no se captura manualmente. Los formularios manuales se limitan a datos que solo el humano puede saber (nombre del cliente, teléfono, monto pagado, etc.).

## Tablas propuestas

### `gyms` (tenants)

- `id`, `name`, `code` , `phone`, `logo_path`, `timezone`, `is_active`, `timestamps`

**code:** (identificador único amigable, generado automático del nombre — ej. "Gimnasio El Fuerte" → `gimnasio-el-fuerte`

### `users`

- Laravel default + `gym_id` (nullable — null para super-admin), `role` (`super_admin`, `owner`, `staff`)
- Relación muchos-a-muchos con `gyms` si un usuario pudiera pertenecer a más de un gym en el futuro (de momento simple: un `gym_id` directo basta)

### `plans` (planes de membresía)

- `id`, `gym_id`, `name`, `duration_days`, `price`, `is_active`, `sort_order`, `timestamps`
- Seeder/acción: al crear un gimnasio, clonar plantillas base

### `members` (clientes del gimnasio)

- `id`, `gym_id`, `full_name` (un solo campo, sin separar nombre/apellido), `phone`, `email` (nullable), `photo_path` (nullable), `notes` (nullable), `is_active`, `timestamps`
- Índice por `gym_id` + `phone` para búsquedas rápidas al check-in

### `memberships` (inscripción de un cliente a un plan, con vigencia)

- `id`, `gym_id`, `member_id`, `plan_id`, `starts_at`, `ends_at`, `status` (`active`, `expired`, `cancelled`) calculado/mantenido por evento, `timestamps`
- `ends_at` se calcula automáticamente (`starts_at + plan.duration_days`) al crear, sin que el usuario lo escriba
- Un cliente puede tener histórico de membresías (renovaciones = nuevos registros, no ediciones destructivas)

### `payments` (pagos)

- `id`, `gym_id`, `member_id`, `membership_id` (nullable, por si es un pago suelto), `amount`, `method` (`cash`, `card`, `transfer`, otros — catálogo simple), `paid_at`, `notes`, `timestamps`
- Un botón "Renovar membresía" en el cliente genera membership + payment en un solo clic, precargando monto según el plan elegido

### `check_ins` (asistencias)

- `id`, `gym_id`, `member_id`, `checked_in_at`, `timestamps`
- Se registran con **un clic** (buscar cliente → botón "Registrar asistencia"), idealmente también accesible por búsqueda rápida de nombre/teléfono

### Alertas (no necesariamente tabla propia)

- Derivadas por query/scheduled command, no una tabla manual:
  - Membresías por vencer en N días
  - Membresías vencidas
  - Clientes sin asistir en N días (posible abandono)
- Se muestran como widgets/listas accionables en el dashboard, con botón directo a "Contactar" / "Renovar"

## Panel de super-admin

Panel de Filament separado (sin tenant), accesible solo para el rol `super_admin`, donde tú das de alta gimnasios nuevos formalmente:

- Formulario único "Nuevo gimnasio": datos del gimnasio (`name`, `phone`, etc.) + datos del dueño (`name`, `email`, `password` o invitación) en una sola pantalla/wizard.
- Al guardar, en un solo paso:
  1. Se crea el registro en `gyms`.
  2. Se crea el `user` con `role = owner` y `gym_id` apuntando al gimnasio recién creado.
  3. Se siembran los planes de plantilla (Mensual, Trimestral, Semestral, Anual, Visita por día) para ese gimnasio.
- Listado de gimnasios existentes con estado (activo/inactivo) y opción de desactivar un gimnasio (suspender acceso sin borrar datos).
- No incluye edición de datos operativos del gimnasio (eso lo hace el propio dueño desde su panel) — el super-admin solo administra altas, estado y datos de cuenta.

## Dashboard principal (por gimnasio)

Widgets clave, todos con acciones de un clic hacia la pantalla correspondiente:

- Total de clientes activos
- Ingresos del mes / del día
- Próximos a vencer (7 días) — con botón renovar
- Vencidos sin renovar — con botón contactar/renovar
- Sin asistir hace X días — con botón contactar
- Asistencias de hoy (contador rápido)

## Experiencia de usuario — principios

1. Todo campo que se pueda calcular (vigencia, estado, adeudo) se calcula, nunca se pide por teclado.
2. Las acciones más frecuentes (check-in, renovar, registrar pago) son de un clic desde la ficha del cliente o desde el dashboard.
3. Catálogos (planes, métodos de pago) se seleccionan de listas, no se escriben.
4. Búsqueda de cliente rápida por nombre/teléfono como punto de entrada a casi todo.

## Checklist end-to-end

- [x] Migraciones creadas: `gyms`, `plans`, `members`, `memberships`, `payments`, `check_ins`, ajuste a `users`
- [x] Modelos Eloquent con relaciones y casts correctos
- [x] Global Scope / tenancy aplicado y verificado (un usuario de gym A no puede ver datos de gym B)
- [x] Seeder de planes de plantilla al crear un gimnasio
- [x] Migración corre limpia desde cero (`migrate:fresh`)
- [x] Verificación manual: crear 2 gimnasios de prueba, confirmar aislamiento de datos entre ambos
- [x] Cálculo automático de `ends_at` y estado de membresía verificado
- [x] Panel de super-admin creado (separado del panel de tenant)
- [x] Formulario "Nuevo gimnasio" crea gym + owner + planes de plantilla en un solo paso
- [x] Listado de gimnasios en super-admin con dueño visible, estado y acción de suspender
- [x] Login único con redirección automática según el rol
- [x] Interfaz en español
- [x] Verificación manual: alta de gimnasio desde la interfaz crea dueño y planes correctamente
- [ ] Factories básicas para pruebas
- [ ] Verificación manual: el dueño entra con su cuenta y solo ve su propio gimnasio

## Notas / pendiente de decidir en fases futuras

- Definir catálogo exacto de `payment methods` (¿fijo o editable por gimnasio?)
- Definir si `memberships.status` se recalcula en cada request o via job programado (recomendado: job diario + cálculo en tiempo real como fallback)
- Definir mecanismo de "Recordatorio por WhatsApp" (link `wa.me` con mensaje precargado es la opción más simple, sin integración de API)
