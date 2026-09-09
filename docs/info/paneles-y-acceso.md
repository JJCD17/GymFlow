# Paneles y control de acceso

## Un solo login para todos

Existe **una sola pantalla de login** (`/admin/login`). No importa el rol: todos entran por ahí y el sistema los manda solo a donde corresponde.

- `/` redirige al login
- `/login` redirige al login del panel principal (esta ruta existe porque Laravel la busca por nombre cuando alguien sin sesión intenta entrar a una zona protegida)

## Los dos paneles

| Panel | Ruta | Quién entra | Color |
|---|---|---|---|
| Principal | `/admin` | Dueño y staff del gimnasio | Ámbar |
| Administración | `/superadmin` | Solo super-admin | Índigo |

## Cómo se decide a dónde va cada quien

Dos piezas trabajan juntas:

**1. `User::canAccessPanel()`** — decide si el usuario *puede* autenticarse en un panel:
- Un usuario inactivo (`is_active = false`) no entra a ningún lado.
- El super-admin puede autenticarse en cualquiera de los dos (necesario para que el login único funcione).
- Un dueño o staff solo puede entrar al panel principal, y únicamente si su gimnasio está activo.

**2. `AuthenticatePanel`** (middleware) — decide a dónde *termina*. Extiende el `Authenticate` de Filament y, antes de delegar en él, redirige al usuario si está en el panel equivocado:
- Si es super-admin y no está en `/superadmin`, lo redirige ahí.
- Si no es super-admin y está en `/superadmin`, lo devuelve a `/admin`.

Se necesitan las dos: si `canAccessPanel` bloqueara al super-admin en el panel principal, el login único fallaría con un error de credenciales antes de que la redirección pudiera ocurrir.

**Por qué extender el middleware en vez de agregar uno propio:** Filament decide el acceso dentro de `Authenticate`, con un `abort(403)`. Un middleware aparte no sirve para esto:
- Registrado *antes* de `Authenticate`, todavía no hay usuario resuelto y no hay a quién redirigir.
- Registrado *después*, nunca se ejecuta, porque el 403 ya cortó la petición.

Al extenderlo, la redirección ocurre en el único punto donde ya se sabe quién es el usuario y aún no se ha rechazado la petición.

**Cuidado al probar esto:** verificar los dos roles dentro de un mismo proceso da resultados falsos, porque la sesión del primero contamina la del segundo. Cada combinación de usuario y ruta debe probarse en un proceso limpio.

## Suspender un gimnasio

Desde el panel de Administración, el botón **Suspender** pone `is_active = false` en el gimnasio. Sus usuarios dejan de poder entrar de inmediato, pero **no se borra ninguna información**. Reactivarlo les devuelve el acceso tal como estaba.

## Alta de un gimnasio nuevo

En el panel de Administración, "Nuevo gimnasio" pide en una sola pantalla los datos del gimnasio y los de su dueño. Al guardar, `CreateGymWithOwner` crea el gimnasio, la cuenta del dueño y sus cinco planes iniciales, todo en una transacción.

La contraseña del dueño se define ahí mismo y hay que compartírsela para su primer acceso.

**Mejora futura:** enviar una invitación por correo para que el dueño defina su propia contraseña, en lugar de asignársela manualmente.

## Notas

- El super-admin no pertenece a ningún gimnasio (`gym_id` en blanco), por eso ve la información de todos.
- La contraseña inicial del super-admin creado durante el desarrollo es `password`; hay que cambiarla antes de usar el sistema en producción.
