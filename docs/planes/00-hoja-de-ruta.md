# Hoja de ruta — lo que sigue

Orden acordado para las próximas fases. Cada una tiene su plan detallado; este archivo solo fija el orden, lo que depende de qué y lo que hay que resolver antes para que una fase no se atore a la mitad.

## Orden

| # | Fase | Plan | Por qué va en este lugar |
| --- | --- | --- | --- |
| 1 | Recepción con QR (check-in) | [07](07-recepcion-check-in.md) | Es lo que más se usa en el día y hoy es lo más lento: buscar al cliente en el listado |
| 2 | Ingresos en el escritorio | [08](08-ingresos.md) | Lo primero que pregunta un dueño: cuánto entró |
| 3 | Rol staff (recepción) | [09](09-rol-staff.md) | Necesario antes de tener gimnasios reales con empleados; ajusta lo que ven las fases 1 y 2 |
| 4 | Personalización del gimnasio | [10](10-personalizacion-del-gym.md) | Logo y color para que el dueño sienta el sistema suyo; el logo también sale en la recepción y en el QR |
| 5 | Días de "por vencer" configurables | [11](11-dias-por-vencer.md) | Cambio chico. **Se puede adelantar** como calentamiento antes de la fase 1, porque la recepción muestra "por vencer" |

## Bloqueantes y pendientes transversales

Cosas que no pidió ninguna fase, pero que alguna de ellas destapa. Van con la fase donde muerden.

### B1 — Los teléfonos no llevan lada de país · bloquea la fase 1

`MemberResource::whatsappUrl()` arma `wa.me/{dígitos}` con lo que se capturó. Un número mexicano de 10 dígitos (`6141234567`) sin el `52` no abre el chat correcto. Hoy pasa desapercibido; en la fase 1 el QR se manda por WhatsApp, así que tiene que funcionar siempre.

**Propuesta:** un `country_code` por gimnasio (default `52`) y un helper que normalice: si el número trae 10 dígitos se antepone la lada, si ya la trae se respeta. Validar el teléfono al capturarlo (10 dígitos).

### B2 — WhatsApp por enlace no puede adjuntar imágenes · decide el diseño de la fase 1

`wa.me` solo abre un chat con texto. No hay forma de mandar la imagen del QR por ahí sin la API de WhatsApp Business (de pago, con aprobación de Meta).

**Propuesta:** el mensaje lleva un **enlace a una página pública** con el QR del cliente (su "credencial"). El cliente la abre, le toma captura o la deja en favoritos. Para el dueño es el mismo flujo de siempre: un botón que abre WhatsApp con el mensaje escrito.

### B3 — Borrar un cliente borra sus pagos · bloquea la fase 2

Las llaves foráneas de `payments`, `memberships` y `check_ins` hacia `members` son `cascadeOnDelete`. Si el dueño borra a un cliente, desaparecen sus pagos y **los ingresos de meses pasados cambian**. En cuanto exista el tablero de ingresos, eso se nota y no se puede explicar.

**Propuesta:** archivar en vez de borrar. `members.is_active` ya existe y hoy no se usa en pantalla: "Archivar" oculta al cliente del listado y de la recepción sin tocar su historial. El borrado real queda solo para el dueño y con aviso, pensado para errores de captura recientes (o se elimina y queda solo archivar).

### B4 — La zona horaria del gimnasio no se usa · afecta las fases 1 y 2

`Gym.timezone` existe, pero todo usa `now()` del servidor. "Asistencias de hoy" e "ingresos de hoy" se cortan a la medianoche del servidor, no a la del gimnasio. Con un solo gimnasio en la misma zona no se nota; con clientes en otra zona, sí.

**Propuesta:** fijar la zona del gimnasio al inicio de cada request del panel (middleware, junto a `RecordLastSeen`), para que `now()` y `today()` ya hablen en la hora del gimnasio. Guardar en UTC y mostrar en la zona local.

### B5 — La cámara del navegador exige HTTPS · afecta la fase 1

`getUserMedia` (leer el QR con la cámara) solo funciona en `https://` o en `localhost`. En producción hace falta certificado. Un lector QR USB, que escribe como teclado, funciona sin esto.

**Propuesta:** la pantalla acepta las dos entradas: cámara y lector USB. El lector es además más rápido y más barato de lo que parece (~$300 MXN).

### B6 — Archivos públicos (logos, fotos) · afecta las fases 1 y 4

Subir logo o foto necesita el disco `public` y `php artisan storage:link` en cada servidor. `members.photo_path` existe, pero el formulario del cliente no tiene campo para subir la foto.

### B7 — Pendientes de verificación y pruebas

- Los planes 02, 03, 05 y 06 tienen sin marcar la verificación en el navegador.
- `ExampleTest` espera un 200 en `/`, que redirige al login (302). Hay que ajustarlo o borrarlo.

## Qué fase toca qué bloqueante

| Fase | Resolver antes o dentro |
| --- | --- |
| 1 — Recepción | B1, B2, B4, B5 (B6 si se muestra la foto) |
| 2 — Ingresos | B3, B4 |
| 3 — Staff | — (revisa lo que ven las fases 1 y 2) |
| 4 — Personalización | B6 |
| 5 — Por vencer | — |

## Ideas que quedaron fuera de este orden

De la revisión general, para retomar después:

- Lista de "a quién contactar hoy" (vencen, vencidos, sin venir) con botón de WhatsApp
- Clientes perdidos y tasa de renovación
- Recibo de pago por WhatsApp
- Congelar membresía (vacaciones, lesión)
- Cumpleaños del día
- Pagos parciales / adeudos
- Columna "última actividad" en el listado de gimnasios del superadmin
