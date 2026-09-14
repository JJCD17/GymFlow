# Escritorio — cómo funciona

El escritorio es la pantalla de inicio del dueño. La idea es que reúna accesos directos y tableros que respondan preguntas que se hace a diario, no que sea una bienvenida decorativa.

## Planes más vendidos

El primer tablero. No está ahí para informar, sino para que el dueño **ajuste sus planes**: ver qué se vende, qué no, y cuánto deja cada uno es lo que permite decidir si sube un precio, cambia una duración o retira un plan que nadie compra. Por eso cierra con un enlace directo a la pantalla de Planes.

- **Se ordena por número de ventas.** Lo más movido arriba.
- **El dinero va en cada fila**, porque las dos cifras juntas cuentan la historia completa: un plan puede venderse poco y dejar más que el que se vende a diario.
- **Los planes sin ventas aparecen en cero**, al final y atenuados. Ese es el dato que revela qué plan hay que ajustar, así que esconderlo sería quitar justo la señal útil.
- **Las barras se miden contra el plan que más vendió**, no contra el total: sirven para comparar un plan con otro de un vistazo. Solo se dibujan donde hubo ventas.

El rango de fechas se elige en el encabezado: este mes, últimos 30 o 90 días, este año, o todo el historial.

## De dónde salen las cifras

`App\Support\PlanSales` arma las filas y es donde vive la regla:

- **Las ventas se cuentan por membresía** (`memberships.created_at`), que es el momento en que se vendió.
- **Los ingresos se suman de los pagos** (`payments.amount`), no del precio del plan. Un pago guarda su propio monto, así que un descuento se refleja tal como entró a la caja. Ver [[planes]] sobre por qué el precio del plan no es el precio cobrado.

Un pago sin membresía (`membership_id` en nulo, posible porque la llave es `nullOnDelete`) no se le atribuye a ningún plan: el `join` lo deja fuera y no infla las cifras de nadie.

Los totales del encabezado se suman de las mismas filas, así que no pueden contradecir lo que se ve abajo.

## Los estilos van en CSS plano

La vista del widget define sus estilos en un bloque `<style>` con las variables del panel, no con clases de Tailwind. Filament sirve su propio CSS compilado y las utilidades de `resources/css/app.css` no llegan hasta ahí: una clase como `flex` o `rounded-full` simplemente no existe dentro del panel y el widget se ve como texto plano. Es el mismo criterio de `filament/sidebar-styles`.

Un detalle que cuesta encontrar: los grises están publicados como `--color-gray-*` **y** `--gray-*`, pero el color de acento solo como `--primary-*`. Escribir `var(--color-primary-600)` no falla de forma visible — deja el elemento sin fondo, que es como se descubrió aquí.
