# Plan 09 — Rol staff (recepción)

**Estado:** Pendiente
**Objetivo:** Que el dueño dé de alta a su personal de recepción, cada quien con su usuario, y que el staff pueda operar el día a día (recibir, inscribir, cobrar) sin ver el dinero del negocio ni cambiar la configuración.

## El hueco que cierra

El rol `staff` existe en `User::ROLE_STAFF`, pero nada lo usa: el dueño no tiene cómo crear usuarios y no hay ninguna regla de qué puede ver cada rol. Hoy, si el dueño quiere que alguien atienda, tiene que prestarle su propia contraseña.

## Cómo lo registra el dueño

Pantalla **Equipo** en Configuración (solo la ve el dueño):

- **Alta:** nombre, usuario (se sugiere `{código-del-gym}-{nombre}`, por ejemplo `ladydabey-ana`), contraseña. El rol siempre es staff: desde aquí no se crean dueños.
- **Listado:** nombre, usuario, activo, **último acceso** (ya existe `last_seen_at` de la fase 06).
- **Desactivar:** saca al usuario en su siguiente clic. `canAccessPanel()` ya revisa `is_active`.
- **Cambiar contraseña** de un empleado (cuando la olvida).
- No se borra un usuario que ya registró cobros o asistencias, solo se desactiva, para no perder quién hizo qué.

## Qué puede hacer cada rol

| Pantalla / acción | Dueño | Staff |
| --- | :---: | :---: |
| Recepción (check-in, fase 07) | ✅ | ✅ |
| Escritorio: hoy en el gimnasio | ✅ | ✅ |
| Escritorio: ingresos (fase 08) | ✅ | ❌ |
| Escritorio: planes más vendidos | ✅ | ❌ (muestra ingresos por plan) |
| Clientes: ver, buscar, alta con cobro | ✅ | ✅ |
| Clientes: editar datos de contacto | ✅ | ✅ |
| Clientes: renovar, registrar asistencia, WhatsApp, enviar QR | ✅ | ✅ |
| Clientes: archivar | ✅ | ❌ |
| Clientes: borrar | ✅ | ❌ |
| Ficha: historial de pagos del cliente | ✅ | ✅ (solo ver) |
| Editar o borrar un pago | ✅ | ❌ |
| Cobrar con monto distinto al precio del plan (descuento) | ✅ | ⚠️ a confirmar |
| Planes (catálogo) | ✅ | ❌ |
| Ajustes | ✅ | ❌ |
| Equipo | ✅ | ❌ |
| Corte de caja de hoy (fase 08) | ✅ | ⚠️ a confirmar: solo lo que cobró él |

**Cómo se implementa:** policies de Laravel (`MemberPolicy`, `PlanPolicy`, `PaymentPolicy`, `UserPolicy`) y `canAccess()` / `canView()` en páginas y widgets. Filament respeta las policies: si no hay permiso, el botón ni aparece. Un helper `User::isOwner()` junto al `isSuperAdmin()` que ya existe.

Las reglas viven en las policies, no solo en ocultar botones: una URL escrita a mano también se rechaza.

## Quién hizo qué

Agregar `user_id` (quien registró) a `payments`, `check_ins` y `memberships`, editando sus migraciones originales. Sirve para:

- El corte de caja por cajero ("Ana cobró $2,400 hoy, $1,800 en efectivo")
- Aclarar un cobro mal hecho
- Mostrarlo en la ficha del cliente: "Cobrado por Ana"

## Decisiones a confirmar

- ¿El staff puede cobrar con descuento (cambiar el monto) o solo el precio de lista?
- ¿El staff ve el corte de caja de su turno?
- ¿Límite de usuarios según el plan de GymFlow del gimnasio? (por ejemplo, Básico: 1 staff). Sería palanca de venta para el superadmin.

## Fuera de alcance

- Permisos configurables por el dueño, casilla por casilla (roles fijos por ahora)
- Horarios o turnos del personal

## Checklist end-to-end

- [ ] `User::isOwner()` / `isStaff()`
- [ ] Pantalla Equipo (alta, listado, desactivar, cambiar contraseña), solo para el dueño
- [ ] Usuario sugerido a partir del código del gimnasio
- [ ] Policies de Member, Plan, Payment, User
- [ ] `canAccess` en Ajustes, Planes, Equipo; `canView` en los widgets de dinero
- [ ] `user_id` en payments, check_ins y memberships; se llena al registrar
- [ ] "Cobrado por" en la ficha y en el corte de caja
- [ ] Pruebas: staff no entra a Ajustes/Planes/Equipo ni por URL; no ve widgets de dinero; no borra ni edita pagos; staff de un gym no ve otro gym; desactivado queda fuera
- [ ] `docs/info/paneles-y-acceso.md` actualizado con la tabla de permisos
- [ ] Verificación en el navegador por el usuario (entrar como dueño y como staff)
