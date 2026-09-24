# Plan 10 — Personalización del gimnasio

**Estado:** Pendiente
**Objetivo:** Que el dueño ponga su logo, su color y sus datos, y que el sistema se vea como *su* sistema, no como uno genérico. Es lo que ve cada día él, su staff y (en la credencial y los mensajes) sus clientes.

**Antes de empezar:** B6 (disco público y `storage:link`). Ver [hoja de ruta](00-hoja-de-ruta.md).

## El hueco que cierra

`gyms.logo_path` y `gyms.phone` existen, pero el dueño no los puede editar: Ajustes solo tiene inasistencia y mensajes. El panel se ve igual para todos los gimnasios.

## Nueva sección en Ajustes: "Tu gimnasio"

- **Nombre** del gimnasio (hoy solo lo cambia el superadmin)
- **Logo:** subir imagen (PNG, JPG o SVG, máximo 2 MB), recorte cuadrado, vista previa. Se guarda redimensionado.
- **Color principal:** una paleta de 8 a 10 colores probados (legibles en claro y oscuro), no un selector libre: un amarillo claro elegido libremente haría ilegibles los botones.
- **Teléfono, dirección, redes** (Instagram, Facebook): salen en la credencial del cliente.
- **Horario** de atención (texto libre por ahora): sale en la credencial.

## Dónde se ve

| Lugar | Qué cambia |
| --- | --- |
| Barra lateral y login del panel | Logo del gimnasio en vez de la marca GymFlow ("con GymFlow" en chico) |
| Botones, enlaces, acentos del panel | Color principal |
| Pantalla de recepción (fase 07) | Logo grande y color |
| Credencial pública del cliente | Logo, color, teléfono, dirección, redes, horario |
| Pestaña del navegador | Favicon con el logo |

**Cómo:** el color se registra por request según el gimnasio del usuario (`FilamentColor::register` en un middleware del panel), así cada gimnasio ve el suyo sin tocar la configuración del panel. La marca (`filament.brand`) ya es una vista; recibe el logo del gimnasio y cae a GymFlow si no hay.

El login es el mismo para todos los gimnasios, así que ahí se queda la marca GymFlow: todavía no sabemos de qué gimnasio es quien entra.

## Fuera de alcance

- Dominio o subdominio propio por gimnasio (`migym.gymflow.mx`). Haría posible un login con su marca; queda para después.
- Temas completos (tipografías, fondos)

## Checklist end-to-end

- [ ] B6: disco público y `storage:link` documentado en el despliegue
- [ ] Columnas nuevas en `gyms` (color, dirección, redes, horario), editando la migración original
- [ ] Sección "Tu gimnasio" en Ajustes
- [ ] Subida de logo con validación y redimensionado
- [ ] Paleta de colores cerrada
- [ ] Logo en la barra lateral, con respaldo a GymFlow
- [ ] Color principal aplicado por gimnasio
- [ ] Favicon del gimnasio
- [ ] Logo y datos en la recepción y en la credencial (fase 07)
- [ ] Pruebas: cada gimnasio ve su logo y color, no el de otro
- [ ] `docs/info/paneles-y-acceso.md` (sección marca) actualizado
- [ ] Verificación en el navegador por el usuario, en tema claro y oscuro
