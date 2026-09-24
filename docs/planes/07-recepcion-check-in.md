# Plan 07 — Recepción con QR (check-in)

**Estado:** Pendiente
**Objetivo:** Que el cliente llegue, escanee su QR y en un segundo sepa (él y la recepción) si está al corriente, cuándo vence y cómo va con sus visitas. Después la pantalla se limpia sola para el siguiente.

**Antes de empezar:** B1 (lada en teléfonos), B2 (el QR va por enlace), B4 (zona horaria), B5 (HTTPS para cámara). Ver [hoja de ruta](00-hoja-de-ruta.md).

## El hueco que cierra

Hoy la asistencia se registra buscando al cliente en el listado y dando clic en "Asistencia". En la hora pico, con fila en la entrada, eso es lento, y el cliente no ve nada: no sabe cuándo vence hasta que alguien se lo dice.

## El QR de cada cliente

- Columna `members.checkin_token`: aleatoria (32 caracteres), única, se genera al dar de alta al cliente. **No es el ID**: un QR con el ID se podría adivinar o fabricar.
- El QR contiene la URL de su credencial pública (`/c/{token}`). Así un solo QR sirve para las dos cosas: en la recepción se lee el token de la URL; si el cliente lo escanea con su cámara, abre su credencial.
- **Regenerar QR** desde la ficha del cliente, por si lo compartió o lo perdió. El anterior deja de servir.

### Credencial pública (`/c/{token}`)

Página sin login, pensada para el celular: logo y nombre del gimnasio, nombre del cliente, el QR grande, su plan y hasta cuándo vence. El cliente le toma captura o la guarda en favoritos.

Se muestra lo mínimo: nada de teléfono, pagos ni historial. Si el token no existe o el gimnasio está fuera, se muestra un aviso genérico.

### Cómo le llega al cliente

- Al terminar el alta, la notificación de "Cliente registrado" trae el botón **Enviar QR por WhatsApp**.
- La misma acción vive en el listado y en la ficha, para reenviarlo.
- Abre `wa.me` con un mensaje editable en Ajustes (`message_qr`), con el enlace a la credencial: *"Hola {cliente}, esta es tu credencial de {gimnasio}. Muéstrala al llegar: {credencial}"*.
- **Descargar QR** (PNG) para imprimirlo o mandarlo a mano.

## Pantalla de recepción

Página del panel del gimnasio (`Recepción`), primera en el menú, a pantalla completa sin distracciones.

### Entrada

- Un campo siempre enfocado que recibe lo que escribe un **lector QR USB** (escribe el texto y un Enter). Si pierde el foco, lo recupera solo.
- Botón **Usar cámara** para leer el QR con la cámara del equipo (tablet o laptop). Librería JS de lectura QR servida localmente.
- **Buscar por nombre** como respaldo: el cliente que olvidó su QR no se queda afuera.

### Resultado

Tarjeta grande, legible a un metro de distancia, con un color de fondo según el estado:

| Estado | Color | Qué pasa |
| --- | --- | --- |
| Al corriente | Verde | Registra la asistencia |
| Por vencer | Amarillo | Registra, y avisa "vence en N días" |
| Vencida | Rojo | **No registra**: "Tu membresía venció el {fecha}. Pasa a recepción." Botón para renovar ahí mismo |
| Ya vino hoy | Azul | No duplica: "Ya registraste tu entrada hoy a las 7:02" |
| QR no válido | Gris | "No reconocemos este código" |

La tarjeta muestra:

- Foto (si tiene) y nombre
- Plan: *Mensual*
- **Vence el 12 de octubre · faltan 18 días**
- Asistencias de este mes, con las últimas visitas (fecha y hora) en una línea
- Racha o constancia, si es sencilla de calcular (por ejemplo: "3 veces esta semana")

### Limpiar para el siguiente

- Botón **OK** grande, o Enter, limpia la tarjeta y deja el campo listo.
- Se limpia sola a los N segundos (8 por defecto).
- Un nuevo escaneo mientras hay una tarjeta en pantalla la reemplaza directo, sin esperar al OK: la fila no se detiene.
- Sonido corto distinto para aceptado y rechazado. El recepcionista no siempre está mirando la pantalla.

## Decisiones a confirmar

- **¿Se deja pasar a un vencido?** Propuesta: no se registra en automático, y el recepcionista puede renovar desde la tarjeta o registrar "de todos modos" (queda marcado). ¿O se prefiere un ajuste por gimnasio: bloquear o solo avisar?
- **¿Quién opera la pantalla?** Con una sesión de dueño abierta en el mostrador, un cliente podría navegar a ingresos o ajustes. Queda resuelto con la fase 09 (sesión de staff). Mientras tanto, la recepción oculta el menú lateral.
- **Tiempo de autolimpieza:** fijo en 8 s o configurable en Ajustes.

## Fuera de alcance

- Torniquetes o puertas automáticas
- Envío automático por la API de WhatsApp Business (ver B2)
- App móvil del cliente (la credencial web cubre esto)

## Checklist end-to-end

- [ ] B1: lada del gimnasio (`country_code`, default 52) y normalización del teléfono
- [ ] B4: zona horaria del gimnasio aplicada en el panel
- [ ] Columna `members.checkin_token` (editar la migración original), generada al crear
- [ ] Generación del QR (paquete PHP, SVG o PNG)
- [ ] Credencial pública `/c/{token}`, mínima y pensada para celular
- [ ] Acción "Enviar QR por WhatsApp" (alta, listado, ficha) con mensaje configurable
- [ ] Acción "Descargar QR" y "Regenerar QR"
- [ ] Pantalla de Recepción: entrada por lector USB con foco permanente
- [ ] Lectura por cámara
- [ ] Búsqueda por nombre como respaldo
- [ ] Tarjeta de resultado por estado (al corriente, por vencer, vencida, ya vino, no válido)
- [ ] Datos de la tarjeta: plan, vencimiento, días restantes, asistencias del mes y últimas visitas
- [ ] No duplicar la asistencia del día
- [ ] Vencido: no registra; renovar desde la tarjeta
- [ ] OK / Enter / autolimpieza / nuevo escaneo reemplaza
- [ ] Sonido de aceptado y rechazado
- [ ] Pruebas: token de otro gimnasio no registra; vencido no registra; doble escaneo no duplica; token regenerado deja de servir
- [ ] `docs/info/recepcion.md`
- [ ] Verificación en el navegador por el usuario (con lector USB y con cámara)
