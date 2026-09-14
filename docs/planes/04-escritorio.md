# Plan 04 — Escritorio del dueño

**Estado:** En progreso
**Objetivo:** Convertir la pantalla de inicio en un tablero útil, empezando por el que ayuda al dueño a decidir sobre sus planes.

## La idea de la pantalla

El escritorio saludaba y nada más. La intención es que reúna accesos directos y tableros que respondan lo que el dueño se pregunta a diario, y que cada uno termine donde se toma la acción correspondiente.

## Hoy en el gimnasio

Va arriba de todo: es lo que el dueño mira al llegar. Cuatro KPIs —vinieron hoy, al corriente, faltan por venir, sin asistir—, la asistencia de los últimos días abiertos y quién ha venido hoy con su hora.

Se actualiza solo cada 30 segundos, porque la pantalla se queda abierta en el mostrador mientras alguien más registra entradas.

Los días que el gimnasio cierra no se cuentan como caída: el comparativo va contra el último día abierto y la gráfica los omite. Si hoy toca cerrado, lo dice.

## Planes más vendidos

El primero, porque cierra un ciclo que ya existe: ahora el dueño puede editar sus planes, pero no tenía con qué decidir *qué* cambiar. Este tablero le dice qué se vende, qué no y cuánto deja cada plan, y lo lleva directo a la pantalla de Planes.

- Se ordena por número de ventas, con el dinero de cada plan en la misma fila
- Los planes sin ventas aparecen en cero al final: ese es el dato que señala cuál ajustar
- Barras proporcionales al plan que más vendió, para comparar de un vistazo
- Rango de fechas: este mes, 30 días, 90 días, este año, todo

Las ventas se cuentan por membresía y el dinero por los pagos cobrados, no por el precio de lista: así un descuento se ve tal como entró a la caja.

## Checklist end-to-end

- [x] Widget de asistencia del día con KPIs
- [x] Comparativo contra el último día abierto, no contra ayer
- [x] Gráfica de días abiertos recientes
- [x] Lista de quién vino hoy, con su hora
- [x] Refresco automático mientras la pantalla está abierta
- [x] Aviso cuando el gimnasio no abre hoy
- [x] Widget de planes más vendidos en el escritorio
- [x] Selector de rango de fechas
- [x] Ingresos y número de ventas por plan
- [x] Planes sin ventas visibles en cero
- [x] Enlace a la pantalla de Planes
- [x] Los ingresos reflejan lo cobrado, no el precio de lista
- [x] Un pago sin membresía no se atribuye a ningún plan
- [x] Verificación: un gimnasio no ve las ventas de otro
- [ ] Verificación en el navegador por el usuario

## Siguientes tableros

Ideas para cuando se retomen, en el mismo espíritu de "responde algo y lleva a la acción":

- Clientes por vencer esta semana, con acceso a contactarlos
- Ingresos del mes contra el anterior
- Horas pico de asistencia, para saber cuándo reforzar personal
