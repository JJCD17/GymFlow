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
| Por vencer | Vence dentro de 7 días | Amarillo |
| Vencida | Ya pasó la fecha | Rojo |
| Sin membresía | Nunca tuvo una | Gris |

**Pendiente:** el umbral de "por vencer" está fijo en 7 días. Sería coherente con el resto del sistema que el dueño pudiera configurarlo, como ya configura los días de inasistencia.

## Filtros del listado

- **Estado de membresía** — al corriente, por vencer, vencidas
- **Sin asistir** — usa los días configurados por el gimnasio en Ajustes, no un número fijo

Un cliente que nunca ha registrado asistencia también aparece en el filtro de inasistencia: para el dueño es igual de importante que quien dejó de venir.

## Acciones desde el listado

**Asistencia** está fuera del menú desplegable, a un solo clic, porque es lo que más se repite en el día.

**Renovar** abre una ventana pequeña con el plan, el monto ya cargado y la forma de pago. Al guardar avisa hasta cuándo quedó vigente.

**Contactar** arma un enlace de WhatsApp con el mensaje que corresponde al estado del cliente: vencida, por vencer, o el de inasistencia si está al corriente pero no ha venido. Los datos del mensaje se rellenan solos.

Solo aparece si el cliente tiene teléfono. El enlace usa `wa.me`, sin integración de API: abre WhatsApp con el mensaje escrito y el dueño decide si lo envía.

## Notas

- El teléfono se limpia de guiones y espacios antes de armar el enlace, porque `wa.me` solo acepta dígitos.
- **Pendiente:** el número no lleva código de país. Funciona si el dueño y el cliente están en el mismo país, pero convendría anteponerlo (52 para México) o guardarlo ya normalizado.
- El listado precarga la membresía vigente y la última asistencia de cada cliente, para no hacer consultas de más al pintar la tabla.
