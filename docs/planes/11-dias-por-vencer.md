# Plan 11 — Días de "por vencer" configurables

**Estado:** Pendiente
**Objetivo:** Que el dueño decida con cuántos días de anticipación una membresía cuenta como "por vencer", igual que ya decide los días de inasistencia.

Cambio chico. Se puede adelantar antes de la fase 07, porque la recepción muestra "por vencer".

## El hueco que cierra

El umbral está fijo en 7 días en dos lugares de `Member`:

- `scopeWithMembershipStatus('expiring')` — filtro del listado
- el accessor `membershipStatus` — color y etiqueta

`docs/info/clientes.md` ya lo marca como pendiente.

Las dos reglas escriben el 7 por separado: una compara en SQL y la otra en PHP. Al volverlo configurable conviene que las dos lean el mismo valor, para que el filtro y la etiqueta no se desfasen.

## Qué cambia

- Columna `gyms.expiring_days` (default 7, de 1 a 30), editando la migración original.
- Campo en Ajustes, en la sección de avisos: *"Avisar que una membresía está por vencer — N días antes"*.
- `Member` lee el valor de su gimnasio en el scope y en el accessor (por fecha, con el último día incluido, como hoy).
- Todo lo que dice "por vencer" usa el mismo valor: listado, ficha, escritorio, mensaje de WhatsApp y la recepción (fase 07).

## Checklist end-to-end

- [ ] Columna `gyms.expiring_days`
- [ ] Campo en Ajustes con validación
- [ ] Scope y accessor usan el valor del gimnasio
- [ ] Pruebas: gimnasio con 3 días vs. uno con 15; el último día cuenta igual en el filtro y en la etiqueta
- [ ] `docs/info/clientes.md`: quitar el "Pendiente" y documentar el ajuste
- [ ] Verificación en el navegador por el usuario
