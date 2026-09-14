# Planes — cómo funciona

## Qué es un plan

Un plan es lo que el gimnasio vende: un nombre, un precio y una duración en días. De ahí salen las dos cosas que el sistema calcula al cobrar: el monto que se precarga y la fecha en que vence la membresía.

Al dar de alta un gimnasio, `CreateGymWithOwner` le siembra los cinco planes de `PlanTemplates` para que pueda cobrar desde el primer día. Son un punto de partida, no una imposición: el dueño los edita, los apaga o crea los suyos desde **Configuración → Planes**.

## El precio del plan no es el precio cobrado

El plan solo precarga el monto en el formulario; el pago guarda su propia cantidad. Por eso un descuento se captura sobre la marcha sin tocar el plan, y subir el mensual de $400 a $500 cambia lo que se cobrará de aquí en adelante sin reescribir los cobros pasados.

La duración funciona igual: `Membership` guarda `starts_at` y `ends_at` al crearse, así que alargar un plan no le regala días a quien ya lo compró.

Esa independencia es lo que permite editar planes con confianza — sin ella, cada cambio de precio reescribiría la contabilidad.

## Desactivar en vez de borrar

Un plan con membresías vendidas es la referencia del historial de esos clientes; borrarlo lo dejaría colgando (la llave foránea de `memberships.plan_id` ni siquiera lo permite).

El botón de borrar **siempre se ve**. Esconderlo dejaba al dueño buscando una opción que no aparecía, sin saber si faltaba un permiso o si él estaba haciendo algo mal. Al intentarlo sobre un plan vendido, el modal explica cuántas membresías lo usan y por qué eso lo bloquea, ofrece **Desactivar en su lugar** como la salida que sí resuelve su intención, y no muestra ningún botón de confirmar: no hay forma de borrarlo por error.

La regla vive en `Plan::deleting()`, no solo en la pantalla. `isDeletable()` es lo que consultan los modales, así que la interfaz y el modelo nunca se contradicen y ninguna otra vía borra un plan vendido por descuido.

Los planes que nunca se usaron sí se borran, con la confirmación normal — el caso de alguien limpiando los sembrados que no le sirven.

## Orden de la lista

`sort_order` decide cómo se acomodan al cobrar, para que el plan más vendido quede arriba en vez de depender del orden alfabético o de captura. Tanto el alta de un cliente como la renovación consultan `Plan::active()->orderBy('sort_order')`.

**El dueño nunca ve ese número.** El formulario pedía "orden en la lista" y eso solo tiene sentido para quien conoce la columna: un 0 sube al principio por una convención invisible, y un 3 no deja el plan en la tercera posición si los demás no son consecutivos. El dueño no piensa en una escala, piensa "quiero este primero".

Así que el número desapareció de la pantalla. En el listado hay un botón **Cambiar orden** que entra al modo de arrastrar y soltar de Filament (`reorderable('sort_order')`), y al soltar se renumera todo solo. El orden se acomoda viéndolo, que es como se toma esa decisión.

Los planes nuevos entran al final: `Plan::creating()` les asigna el siguiente número del gimnasio. Antes heredaban el 0 del formulario y se colaban hasta arriba, empatados con el primero — una posición que nadie eligió. La numeración se calcula entre los planes del propio gimnasio, así que los de otro no la empujan.

## Aislamiento entre gimnasios

`Plan` usa el trait `BelongsToGym`, así que el scope global filtra por `gym_id` y lo asigna solo al crear. Un dueño no ve, edita ni borra los planes de otro gimnasio, sin que el recurso de Filament tenga que hacer nada al respecto.
