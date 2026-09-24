# Plan 08 — Ingresos en el escritorio

**Estado:** Pendiente
**Objetivo:** Que el dueño abra el escritorio y vea cuánto dinero entró hoy, esta semana, este mes o en el periodo que elija, comparado con el periodo anterior y separado por forma de pago.

**Antes de empezar:** B3 (archivar en vez de borrar, para que los ingresos pasados no cambien) y B4 (zona horaria, para que "hoy" sea el hoy del gimnasio). Ver [hoja de ruta](00-hoja-de-ruta.md).

## El hueco que cierra

Los pagos solo se ven dentro de la ficha de cada cliente. Para saber cuánto entró en el mes, el dueño tendría que sumar a mano.

## Qué muestra

Widget en el escritorio del dueño, debajo de "Hoy en el gimnasio":

- **Selector de periodo:** hoy, esta semana, este mes, este año, y un rango personalizado. Mismo patrón que el selector de "Planes más vendidos" (`PlanSales::RANGES`).
- **Total cobrado** en grande, con la **comparación contra el periodo anterior** del mismo tamaño ("+12 % vs. la semana pasada"). Si hoy es miércoles, "esta semana" se compara con lunes a miércoles de la anterior, no con la semana completa.
- **Por forma de pago:** efectivo, tarjeta y transferencia, con monto y porcentaje. El efectivo es lo que el dueño tiene que cuadrar en caja.
- **Número de cobros** y **ticket promedio**.
- **Gráfica** de barras por día (semana o mes) o por mes (año).
- **Nuevos vs. renovaciones:** cuánto vino de clientes nuevos y cuánto de renovaciones. Dice si el gimnasio crece o solo mantiene.

## Reglas

- Se suma `payments.amount` por `paid_at`, que es lo que realmente entró a la caja (con descuentos incluidos), igual que en "Planes más vendidos".
- Los días que el gimnasio cierra aparecen en cero en la gráfica, pero no se señalan como caída.
- Solo el dueño lo ve. Hasta la fase 09 no hay staff, así que no hay que ocultarlo todavía; esa fase lo restringe.

## Corte de caja (opcional en esta fase)

Una vista "Cobros de hoy" con la lista de pagos del día (hora, cliente, concepto, forma de pago, monto) y el total en efectivo. Con la fase 09 se le agrega quién cobró.

## Fuera de alcance

- Gastos, utilidad o egresos (el sistema solo conoce lo que entra)
- Exportar a Excel o PDF (se puede agregar después sobre la misma consulta)
- Facturación

## Checklist end-to-end

- [ ] B3: archivar clientes en vez de borrarlos (sin cascada sobre pagos)
- [ ] B4: zona horaria del gimnasio aplicada
- [ ] Clase de soporte `Revenue` (totales por periodo, anterior comparable, por método, por día)
- [ ] Widget con selector de periodo
- [ ] Comparación contra el periodo anterior equivalente
- [ ] Desglose por forma de pago
- [ ] Número de cobros y ticket promedio
- [ ] Gráfica por día o mes
- [ ] Nuevos vs. renovaciones
- [ ] (Opcional) Vista de cobros de hoy
- [ ] Pruebas: sumas por periodo, comparación, aislamiento entre gimnasios
- [ ] `docs/info/escritorio.md` actualizado
- [ ] Verificación en el navegador por el usuario
