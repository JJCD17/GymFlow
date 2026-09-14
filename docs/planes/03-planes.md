# Plan 03 — Planes de membresía (panel del dueño)

**Estado:** En progreso
**Objetivo:** Que el dueño administre sus propios planes —nombre, precio y duración— en vez de quedarse con los cinco que el sistema crea al abrir el gimnasio.

## El hueco que cierra

Al dar de alta un gimnasio se le siembran planes de ejemplo (`PlanTemplates`). Hasta ahora eran los únicos que podía usar: el plan aparece en una lista al registrar o renovar a un cliente, pero no había dónde cambiarle el precio, el nombre ni los días que dura. Un gimnasio que cobra $500 el mensual tenía que aceptar los $400 sembrados, o pedir que se lo cambiaran en la base de datos.

## Pantalla de planes

Va en **Configuración**, junto a Ajustes: no es algo que se toque a diario, sino cuando cambian los precios.

- **Nombre** — cómo lo nombra el gimnasio ("Mensual", "Quincena", "Estudiantes")
- **Precio** — el monto que se precarga al cobrar
- **Duración en días** — de ahí sale la fecha de vencimiento
- **Activo** — un plan apagado deja de ofrecerse sin borrar su historial

El **orden** en que aparecen al cobrar no se captura: se arrastra desde el listado con el botón *Cambiar orden*. Pedir un número obligaba al dueño a traducir "quiero este primero" a una escala que no ve —un 0 sube al inicio por una convención invisible, y un 3 no lo deja tercero si los demás no son consecutivos—. Los planes nuevos entran al final, que es donde uno espera lo recién agregado.

El listado muestra cuántas membresías se han vendido con cada plan, para que el dueño vea cuál se usa antes de cambiarlo.

## Cambiar un precio no toca lo ya cobrado

El precio del plan solo precarga el monto al momento de cobrar; el pago guarda su propia cantidad. Subir el mensual de $400 a $500 cambia lo que se cobrará de aquí en adelante y deja intactos los pagos anteriores.

Lo mismo con la duración: la membresía guarda sus fechas al crearse, así que alargar un plan no le regala días a quien ya lo compró.

## Borrar vs. desactivar

Un plan con membresías vendidas no se borra: el historial de esos clientes quedaría sin referencia. En ese caso se desactiva, que lo saca de las listas de cobro y conserva lo pasado. Solo se puede borrar un plan que nunca se usó.

El botón de borrar no se esconde. Un botón ausente no explica nada y deja al dueño preguntándose si el problema es un permiso o algo que él hizo mal. Al intentarlo, el aviso dice cuántas membresías dependen del plan y le ofrece desactivarlo ahí mismo; el botón de confirmar simplemente no está, así que no hay manera de borrarlo por error.

## Checklist end-to-end

- [x] Recurso de planes en el panel del dueño, en Configuración
- [x] Alta y edición: nombre, precio, duración y activo
- [x] Orden por arrastrar y soltar desde el listado, sin capturar números
- [x] Los planes nuevos entran al final de la lista
- [x] Listado con conteo de membresías vendidas por plan
- [x] Activar/desactivar desde el listado
- [x] Borrar bloqueado cuando el plan ya tiene membresías, con aviso que explica el motivo
- [x] La regla de borrado vive en el modelo, no solo en la pantalla
- [x] Las listas de cobro (alta y renovación) respetan activo y orden
- [x] Verificación: un gimnasio no ve ni edita los planes de otro
- [ ] Verificación en el navegador por el usuario
