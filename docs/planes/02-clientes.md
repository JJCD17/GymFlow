# Plan 02 — Clientes (panel del dueño)

**Estado:** En progreso
**Objetivo:** Construir la pantalla de clientes: darlos de alta con su membresía y pago en un solo paso, ver de un vistazo quién está al corriente y quién no, y dejar las acciones frecuentes a un clic desde ahí.

## Por qué esta pantalla primero

Es el punto de entrada de casi todo el sistema: las asistencias, los pagos y las alertas cuelgan de un cliente. Sin clientes cargados, el escritorio no tendría nada que mostrar.

## Alta de un cliente

Una sola pantalla que refleja lo que pasa en la realidad: llega alguien nuevo, paga y queda inscrito.

- **Datos del cliente:** nombre completo y teléfono. El correo y las notas quedan opcionales y plegados, para no estorbar en el caso común.
- **Membresía:** se elige el plan de una lista. La fecha de vencimiento la calcula el sistema y se muestra antes de guardar, para que el dueño confirme sin hacer cuentas.
- **Pago:** el monto viene precargado del plan y el método se elige de una lista. Puede ajustarse si hubo un descuento.

Todo se guarda junto en una transacción: cliente, membresía y pago.

## Listado de clientes

La información que el dueño necesita ver sin abrir a nadie:

- Nombre y teléfono
- Estado de la membresía, con color: al corriente, por vencer, vencida
- Fecha de vencimiento y días restantes
- Última asistencia

**Filtros de un clic**, que responden a las preguntas que el dueño se hace a diario:
- Al corriente / Por vencer / Vencidos
- Sin asistir en los últimos días

**Búsqueda** por nombre o teléfono.

## Acciones a un clic

Desde el listado, sin abrir formularios largos:

- **Registrar asistencia** — deja la entrada del día
- **Renovar** — elige plan, calcula fechas y registra el pago
- **Contactar** — abre WhatsApp con un mensaje ya escrito según el caso (por vencer, vencido, sin asistir)

## Ficha del cliente

Al abrir a alguien: sus datos, el estado actual de su membresía, su historial de pagos y sus últimas asistencias.

## Checklist end-to-end

- [x] Recurso de clientes en el panel del dueño
- [x] Alta en una sola pantalla: cliente + membresía + pago en una transacción
- [x] Vencimiento calculado y visible antes de guardar
- [x] Listado con estado de membresía por color y días restantes
- [x] Filtros por estado y por inasistencia
- [x] Búsqueda por nombre y teléfono
- [x] Acción de registrar asistencia
- [x] Acción de renovar membresía
- [x] Acción de contactar por WhatsApp
- [x] Verificación: alta crea membresía y pago; renovar continúa desde el vencimiento
- [x] Verificación: un gimnasio no ve los clientes de otro
- [x] Ficha con historial de membresías, pagos y asistencias
- [ ] Verificación en el navegador por el usuario

## Ajustes del gimnasio

Cada gimnasio opera distinto, así que estos valores no van fijos en el código. El dueño los edita desde una pantalla de **Ajustes** en su menú:

- **Días sin asistir para considerar abandono** — arranca en 7. En un gimnasio de barrio una semana puede ser normal; en uno de clientes muy activos, cuatro días ya es señal.
- **Mensajes de contacto** — uno por situación: membresía por vencer, membresía vencida, y cliente sin asistir.

Los mensajes vienen ya redactados y el dueño los ajusta a su forma de hablarle a sus clientes. Puede insertar el nombre del cliente, el del gimnasio y la fecha de vencimiento, que el sistema rellena al momento de enviar.

Los mensajes **no mencionan GymFlow**: al cliente del gimnasio le llega un mensaje de *su* gimnasio, y una marca ajena ahí se leería como publicidad. El producto se le vende al dueño, no a sus clientes.

## Checklist — ajustes

- [x] Campos de configuración en el gimnasio (días de inasistencia y mensajes)
- [x] Pantalla de Ajustes en el panel del dueño
- [x] Mensajes con datos que el sistema rellena al enviar
- [x] Las alertas de inasistencia usan el número configurado, no uno fijo
