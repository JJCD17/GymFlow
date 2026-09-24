# Clientes — cómo funciona

## Alta en un solo paso

`CreateMember` guarda en una transacción al cliente, su membresía y su pago. Refleja lo que pasa en el mostrador: llega alguien, paga y queda inscrito. Si algo falla a media captura, no queda un cliente sin membresía ni un cobro sin registrar.

El formulario solo pide lo que el sistema no puede saber. Al elegir el plan, el monto se llena solo con su precio (editable, por si hubo descuento) y debajo de la fecha de inicio aparece cuándo vence, para que el dueño confirme sin sacar cuentas.

## Renovaciones que no regalan ni quitan días

`RegisterMembership` decide la fecha de inicio según el caso:

- Si la membresía sigue vigente, la nueva arranca **al día siguiente de que termina la actual**. Renovar antes de tiempo no le quita días al cliente.
- Si ya venció, arranca hoy.

Cada renovación es un registro nuevo, nunca una edición de la anterior, así que el historial del cliente queda completo.

## Estado de la membresía

`membership_status` deriva del vencimiento, no se guarda en ninguna columna:

| Estado | Cuándo | Color |
|---|---|---|
| Al corriente | Vence en más de 7 días | Verde |
| Por vencer | Vence dentro de los días configurados (7 por defecto) | Amarillo |
| Vencida | Ya pasó la fecha | Rojo |
| Sin membresía | Nunca tuvo una | Gris |

El umbral de "por vencer" lo decide el dueño en Ajustes (`gyms.expiring_days`, de 1 a 30), igual que los días de inasistencia. Cuenta por fecha y con el último día incluido: con 7 días, una membresía que vence dentro de exactamente 7 días ya está por vencer, y el día mismo que vence también.

La regla vive en dos lugares que tienen que coincidir: el accessor `membership_status` (color y etiqueta, lee `$member->gym`) y `scopeWithMembershipStatus` (filtro del listado). El scope recibe los días como parámetro, porque un scope no sabe de qué gimnasio es la consulta. El listado precarga `gym` para que la etiqueta no haga una consulta por fila.

## Filtros del listado

- **Estado de membresía** — al corriente, por vencer, vencidas
- **Sin asistir** — usa los días configurados por el gimnasio en Ajustes, no un número fijo

Un cliente que nunca ha registrado asistencia también aparece en el filtro de inasistencia: para el dueño es igual de importante que quien dejó de venir.

## Acciones desde el listado

**Asistencia** está fuera del menú desplegable, a un solo clic, porque es lo que más se repite en el día.

**Renovar** abre una ventana pequeña con el plan, el monto ya cargado y la forma de pago. Al guardar avisa hasta cuándo quedó vigente.

**Contactar** arma un enlace de WhatsApp con el mensaje que corresponde al estado del cliente: vencida, por vencer, o el de inasistencia si está al corriente pero no ha venido. Los datos del mensaje se rellenan solos.

Solo aparece si el cliente tiene teléfono. El enlace usa `wa.me`, sin integración de API: abre WhatsApp con el mensaje escrito y el dueño decide si lo envía.

### Vista previa en Ajustes

Debajo de cada mensaje, Ajustes muestra una burbuja de WhatsApp con el texto ya llenado: el nombre real del gimnasio, un cliente de ejemplo (Ana López) y una fecha que sale de los días configurados. Se actualiza mientras el dueño escribe.

**Por qué:** el dueño no sabe qué es `{cliente}`. Viendo solo la plantilla, cree que el cliente va a recibir las llaves tal cual. La vista previa usa `MessageTemplates::render()`, la misma función que llena el mensaje al enviarlo, así que lo que se ve es exactamente lo que va a llegar.

## Días que el gimnasio no abre

El dueño marca en Ajustes qué días de la semana cierra. Eso **no bloquea nada**: su único efecto es que esos días no cuentan como ausencia del cliente.

**Por qué importa:** si un gimnasio cierra domingos y el umbral de abandono es 7 días, medir en días de calendario hace que la alerta salte antes de tiempo, porque incluye días en que el cliente no podía venir. `Gym::absenceThresholdDate()` retrocede día por día y solo descuenta los que el gimnasio abrió, así que "7 días sin venir" significa siete oportunidades perdidas de verdad.

### Por qué no un horario completo

Se consideró capturar el horario con turnos (por ejemplo 7-13 y 15-21) para impedir registrar asistencia con el gimnasio cerrado. Se descartó: quien registra la asistencia es el dueño o su encargado, en el mostrador y con el negocio abierto, así que el error que evitaría no ocurre en la práctica. El costo de capturar y mantener dos turnos por día no se paga.

**Cuándo reconsiderarlo:** si se agregan clases con cupo y horario, o un tótem donde el propio cliente registra su entrada. Ahí sí hace falta saber si el gimnasio está abierto en ese momento.

## Una asistencia por día

Un cliente no puede registrar más de una asistencia el mismo día. Si ya tiene una, el botón aparece deshabilitado con la leyenda "Ya registró asistencia hoy".

**Por qué:** sin esta regla, cada clic dejaba un registro nuevo, y el historial acababa con tres entradas del mismo minuto que no representan visitas reales. El dato existe para responder "¿vino hoy?" y "¿cuántos días lleva sin venir?", y para eso una por día basta.

Sí hay quien va dos veces al día (entrena en la mañana, clase en la tarde), pero es poco común y contarlo dos veces no cambia ninguna de esas dos respuestas.

La regla vive en `Member::hasCheckedInToday()`, así aplica en cualquier lugar donde se registre una asistencia, no solo en el botón. La acción la comprueba dos veces —al pintar el botón y al ejecutarse— porque el botón deshabilitado no protege de dos pestañas abiertas ni de un clic antes de que la página se actualice.

**Pendiente:** la comparación usa la fecha del servidor. Cuando se aplique la zona horaria del gimnasio, esta regla debe usarla también, o un gimnasio en otro huso podría ver bloqueada una asistencia que para él es de otro día.

## Ficha del cliente

Al hacer clic en alguien del listado se abre su ficha, no el formulario de edición: casi siempre se entra a consultar, no a corregir datos.

Arriba, lo que responde de un vistazo cómo está: estado de la membresía, plan actual, vencimiento con días restantes y última visita. Debajo, sus datos y desde cuándo es cliente. Las mismas acciones del listado (asistencia, renovar, contactar) están en el encabezado.

Abajo, tres historiales de solo lectura: membresías, pagos y asistencias. No se editan a mano porque nacen de las acciones; corregirlos ahí desincronizaría el historial.

### Estado de cada membresía

Una membresía puede estar en tres estados, que dependen de su propio periodo:

- **Vigente** — ya empezó y no ha terminado
- **Programada** — se renovó por adelantado y arranca cuando termine la actual
- **Terminada** — su fecha ya pasó

Sin el estado "Programada", una renovación anticipada aparecería como vigente al mismo tiempo que la membresía en curso, lo que se leería como si el cliente tuviera dos activas.

### Acciones compartidas

Renovar, contactar y el cálculo de días restantes viven en `MemberResource`, no en la tabla, porque el listado y la ficha usan los mismos.

## Notas

- El teléfono se limpia de guiones y espacios antes de armar el enlace, porque `wa.me` solo acepta dígitos.
- **Pendiente:** el número no lleva código de país. Funciona si el dueño y el cliente están en el mismo país, pero convendría anteponerlo (52 para México) o guardarlo ya normalizado.
- El listado precarga la membresía vigente y la última asistencia de cada cliente, para no hacer consultas de más al pintar la tabla.
