# Suscripciones de gimnasios — cómo funciona

Lo que GymFlow le cobra a cada gimnasio. No confundir con los planes que un gimnasio le vende a sus clientes (ver [[planes]]): esto es un nivel arriba, del superadmin hacia el gimnasio.

- **`SubscriptionPlan`** — el catálogo: Mes de prueba, Semanal, Mensual, Semestral, Anual. Mismos campos que un plan de gimnasio (nombre, precio, duración en días, activo, orden) más `is_trial` para distinguir la cortesía de la paga.
- **`GymSubscription`** — qué contrató un gimnasio: plan, `starts_at`, `ends_at`, `status` y notas.

## `is_active` de un gimnasio ya no se captura, se calcula

Antes era una columna que el superadmin prendía y apagaba. El problema es que una columna así no sabe nada de fechas: un gimnasio que dejó de pagar seguía adentro hasta que alguien se acordara de apagarlo a mano.

Ahora hay dos causas independientes por las que un gimnasio queda fuera, y `is_active` es el resultado de ambas:

1. **`suspended_at`** — la suspensión manual, la que ya existía. Se sigue accionando desde el listado con Suspender/Reactivar.
2. **Suscripción vencida** — no hay ninguna `GymSubscription` vigente (`status = active` y `ends_at >= hoy`).

```
is_active = suspended_at es null  Y  hay suscripción vigente
```

Se calcula al vuelo con un accessor, no se guarda. Una columna guardada tendría que mantenerse sincronizada con la fecha de vencimiento por un cron, y el día que ese cron fallara el gimnasio seguiría adentro sin pagar. Es el mismo criterio que ya usaba `Membership::isExpired` para los clientes: la fecha manda, no una bandera.

`User::canAccessPanel()` es el único lugar que decide el acceso, así que basta con que lea `Gym::is_active` para que las dos causas apliquen.

## La sesión abierta se corta sola

Filament revalida `canAccessPanel()` en **cada** request, así que un dueño con la sesión abierta no se queda adentro: en cuanto vence su suscripción o lo suspenden, la siguiente pantalla que toque le responde 403. No hace falta esperar a que cierre sesión ni correr nada que lo expulse.

Con un detalle que costó encontrar: `canAccessPanel()` relee el gimnasio con `$this->gym()->first()` en vez de usar `$this->gym`. La relación del usuario autenticado se queda cargada entre peticiones, así que la propiedad devuelve el gimnasio tal como estaba al iniciar sesión — un gimnasio suspendido hace un minuto seguía apareciendo activo y el dueño entraba igual. Al releerlo, cada request ve el estado real.

## Se explica en vez de responder 403

Un 403 pelón se lee como "algo se descompuso", y el dueño no tiene forma de saber que el asunto es administrativo ni a quién preguntarle. `AuthenticatePanel` se adelanta y devuelve una pantalla que dice qué pasó, distinguiendo pausado de vencido, y que para el vencimiento muestra la fecha.

El tono importa: el texto evita la urgencia comercial de los muros de pago. No hay contador ni botón de pagar. Lo que hace es tranquilizar —la información sigue completa y vuelve tal como quedó— porque el miedo real de quien ve esa pantalla es haber perdido a sus clientes.

**Salir siempre se permite.** El middleware deja pasar `*/logout` antes de validar nada. Sin esa excepción el aviso se devolvía a sí mismo: cerraba la sesión pero respondía la misma pantalla en lugar de la redirección, y parecía que el botón no hacía nada. Peor aún, alguien con el gimnasio fuera quedaría atrapado sin poder entrar con otra cuenta.

## Por qué se distingue suspendido de vencido

Los dos dejan al gimnasio fuera, pero significan cosas opuestas: uno lo apagamos nosotros a propósito, el otro es que dejó de pagar. El listado los muestra con etiquetas y colores distintos, y al reactivar un gimnasio vencido el aviso advierte que seguirá sin entrar hasta que se le registre una suscripción nueva — reactivarlo no le regala tiempo.

La columna de plan muestra cuándo vence y cuántos días faltan, y el filtro *Vencen en 15 días* es para cobrar antes de que el gimnasio se quede fuera.

## Un gimnasio nace con su suscripción

El alta pide el plan contratado junto con los datos del dueño. Sin eso, el gimnasio nacería vencido y su dueño no podría entrar el primer día. `CreateGymWithOwner` la crea dentro de la misma transacción que el gimnasio, el dueño y los planes iniciales.

## La fecha de vencimiento se calcula pero se puede ajustar

`ends_at` sale de `starts_at + duration_days` del plan, igual que en las membresías de clientes. Queda editable a propósito: un acuerdo puntual con un gimnasio no debería obligar a inventar un plan nuevo en el catálogo solo para esa fecha.

Renovar es registrar otra suscripción, no editar la anterior. Así el historial queda completo y se ve qué se le ha cobrado a cada gimnasio.

## Borrar vs. desactivar un plan del catálogo

Mismo criterio que los planes del gimnasio: un plan ya contratado no se borra porque es la referencia de ese historial. Se desactiva, deja de ofrecerse al contratar, y lo pasado se conserva.
