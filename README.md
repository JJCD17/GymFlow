# GymFlow

**GymFlow** es una aplicación SaaS sencilla para gimnasios pequeños y medianos.

## ¿Qué resuelve?

- Clientes y sus datos básicos.
- Membresías y fechas de vencimiento.
- Pagos y adeudos.
- Asistencias / check-ins.
- Clientes próximos a vencer o con membresía vencida.
- Clientes que llevan varios días sin asistir, para detectar posibles abandonos.
- Recordatorios y contacto rápido.

## Multi-tenant

GymFlow es **multi-gimnasio (multi-tenant)**: varios gimnasios usan la misma aplicación y base de datos, pero cada gimnasio solo puede acceder a sus propios clientes, pagos, asistencias y demás datos.

## Filosofía del proyecto

La prioridad es mantenerlo **simple, moderno y rápido**, enfocado en resolver problemas reales de gimnasios pequeños. Se evita deliberadamente convertirlo en un ERP con funciones innecesarias.

## Stack técnico

- [Laravel 13](https://laravel.com)
- [Filament 5](https://filamentphp.com) como panel de administración
- MySQL
- PHP 8.3+

## Documentación del proyecto

- [`docs/planes/`](docs/planes) — planes de trabajo por fase, con checklist de verificación end-to-end.
- [`docs/info/`](docs/info) — documentación viva: decisiones tomadas, mejoras futuras, notas importantes.

## Desarrollo local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

El seeder crea el super-admin y un gimnasio de ejemplo con clientes en distintos estados de membresía. Solo corre en entorno local.

Las credenciales del super-admin se definen en `.env` (`SUPER_ADMIN_EMAIL` y `SUPER_ADMIN_PASSWORD`). El dueño del gimnasio de ejemplo es `dueno@demo.test` con esa misma contraseña.

## Acceso

Todos entran por `/admin/login`. Según su rol, el sistema los lleva a donde corresponde:

- **Dueño del gimnasio** → `/admin`, para su operación diaria.
- **Super-admin** → `/superadmin`, para dar de alta gimnasios.
