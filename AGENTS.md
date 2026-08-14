# AGENTS.md

## Proyecto

SIGG — Sistema Integral de Gestión para Gimnasios. Laravel 12 + PHP 8.2, Blade + Alpine.js, Tailwind CSS 4 (Vite 7), MariaDB/MySQL. Todo el dominio en español. Aplicación server-rendered (sin API, sin Sanctum).

## Comandos

```bash
composer setup          # Instalación completa: composer install, .env, key, migrate, npm, build
composer dev            # server + queue:listen + pail + vite en paralelo
composer test           # config:clear + php artisan test
./vendor/bin/pint       # Formato/lint PHP
npm run build           # Build de assets (Tailwind 4 + Alpine vía Vite)
npm run dev             # Vite dev server (HMR)
```

Test individual: `php artisan test --filter=TestClassName` o `php artisan test tests/Feature/X.php`

## Base de datos

- **Dev**: MariaDB/MySQL (`sistema_gimnasio`, 127.0.0.1:3306, root sin password en Laragon). Configurar en `.env`.
- **Tests**: SQLite in-memory (definido en `phpunit.xml`), `RefreshDatabase`.
- **Naming (crítico, no es el default de Laravel)**:
  - PKs personalizadas: `id_alumno`, `id_mem`, `id_pag`, `id_productos`, `id_venta`, `id_caja`, `id_visi`, `id_cuota`, `id_comision`, `id_metod`, `id_sede`... Siempre declarar `$primaryKey` en el modelo.
  - FKs con prefijo `fk`: `fkalum`, `fksede`, `fkuser`, `fkmem`, `fkventa`... Declarar siempre en las relaciones.
  - Columnas con prefijos: `alum_*`, `mem_*`, `prod_*`, `gas_*`, `pag_*`, `visi_*`, `sede_*`.
  - **Tabla de alumnos es `alumno` (singular)**, no `alumnos`.
  - **El modelo `Asistencia` usa la tabla `visitas`** (PK `id_visi`).
  - Modelos con `$guarded = []`; solo `User` usa `$fillable`.
- Migraciones en `database/migrations/`: `0001_01_01_000003_create_domain_tables.php` (tablas base) + migraciones 000004–000015 (membresias_alumno, cuotas, comisiones, auditoría, notificaciones, índices, etc.).

## Arquitectura

- **Rutas**: `routes/web.php`. Públicas: login, recuperación de contraseña, `/asistencia` (registro público). Todo lo demás dentro del grupo `auth` con middleware `permission:*`.
- **Middlewares** (alias en `bootstrap/app.php`): `guest`, `role`, `permission`, `sede`.
- **Controllers delgados**: `app/Http/Controllers/` — reciben petición, llaman Service/Policy, retornan respuesta (soportan AJAX vía `$request->expectsJson()`).
- **Services** (`app/Services/`): lógica de negocio — AlumnoService, SaleService, PaymentService, CommissionService, PenaltyService, CashClosingService, MembresiaService, FollowUpService, NotificationService, AuditService, AttendanceService, ReportService.
- **Policies** (`app/Policies/`): Alumno, Venta, Pago, Gasto, Caja, Comision, Sede, Usuario, MembresiaAlumno, AuditLog.
- **Form Requests** (`app/Http/Requests/`): validaciones por módulo.
- **View Components** (`app/View/Components/`): `Sidebar` (menú por roles + `canAccessMenuItem()`).
- **Vistas**: `resources/views/{módulo}/`. Componentes Blade: `x-button`, `x-badge`, `x-table`, `x-modal`, `x-modal-form`, `x-search-input`, `x-select-filter`, `x-toast-notifications`, `x-page-loader`, `x-stat-card`, `x-notification-dropdown`, iconos `x-icon-*`.
- **Frontend**: Tailwind 4 (Vite), Alpine.js, Font Awesome 6 CDN, fuente Inter. Layout `layouts/app.blade.php`.

## Módulos (lo que hace el sistema)

| Módulo | Permiso | Funcionalidad |
|---|---|---|
| Dashboard | — | Vista diferenciada por rol (`dashboard/{admin,local,redes,asistencia}`) + `/graficos` |
| Alumnos | `alumnos.*` | CRUD, ficha con tabs (info, membresías, pagos, asistencias), control por sede, SoftDeletes |
| Asistencia | `asistencias.*` | Registro con `tipo_ingreso` (codigo/dni/qr/huella); vista pública sin auth en `/asistencia` |
| Membresías | `membresias.*` | Catálogo (`membresias`) + historial por alumno (`membresias_alumno`), asignar/renovar |
| Productos | `productos.*` | CRUD con stock y stock mínimo |
| Ventas | `ventas.*` | 3 tipos: `producto`, `membresia`, `rapida`; ventas reservadas; stock automático; transacciones |
| Pagos/Cuotas | `pagos.*` | Pagos completos/incompletos, pagos parciales, tabla `cuotas`, abonos, cuotas vencidas |
| Comisiones | `comisiones.ver` | Comisiones por venta/membresía, penalizaciones, liquidación |
| Gastos | `gastos.*` | Flujo de aprobación: `pendiente` → `aprobado`/`rechazado` |
| Caja | `caja.*` | Apertura/cierre por empleado, movimientos, anulación, PDF de cierre |
| Seguimiento | `seguimiento.ver` | Vencimientos/vencidos, enlace WhatsApp por alumno |
| Notificaciones | `notificaciones.ver` | Internas, dropdown, comandos agendados |
| Reportes | `reportes.ver` | 8 reportes: ventas, membresías, productos, comisiones, gastos, caja, vencimientos |
| Usuarios | `usuarios.*` | CRUD + toggle de estado |
| Sedes | `sedes.*` | CRUD + toggle de estado |
| Auditoría | `auditoria.ver` | Registro de operaciones críticas (`audit_logs`) vía `AuditService` |

## Roles y permisos (Spatie)

- Roles: `Administrador` (todos), `Local` (operación completa), `Redes` (alumnos/membresías/seguimiento/asistencia), `Asistencia` (solo alumnos ver + asistencia).
- Permisos granulares (`alumnos.ver`, `ventas.crear`, `gastos.aprobar`, `caja.cerrar`, etc.) definidos en `database/seeders/RolePermissionSeeder.php` (también migra usuarios legacy al rol Spatie).
- El campo legacy `rol` (int: 0=Admin, 1=Empleado, 2=Asistencia, 3=Ventas) sigue en `User` como respaldo; `getNombreRolAttribute()` usa Spatie primero.
- Middleware: `permission:...` en rutas, `role:...`/`can()` según el caso.

## Reglas de negocio clave

- **Penalización de comisiones**: 7 días de tolerancia, luego **S/5 por semana de retraso**; `comision_final = max(0, comision_base - penalizacion)`.
- **Ventas**: se registran con `DB::transaction()`, decrementan stock, generan comisión.
- **Pagos**: soportan pagos parciales con `saldo`; las cuotas se crean a partir de la venta y pueden marcarse vencidas.

## Seeders

- `DatabaseSeeder` (idempotente, usa `firstOrCreate`): 2 sedes, métodos de pago, categorías, 6 planes de membresía, 4 usuarios demo, 12 alumnos, productos, pagos, ventas, cuotas, asistencias, gastos, movimientos de caja, comisiones.
- Usuarios demo: `admin@gym.com/admin123`, `recepcion@gym.com/recepcion123`, `ventas@gym.com/ventas123`, `empleado@gym.com/empleado123`.
- Ejecutar `RolePermissionSeeder` primero (roles/permisos) — lo invoca `DatabaseSeeder`.

## Tests

- PHPUnit (`phpunit.xml`), SQLite in-memory, `TestCase::setUp()` usa `withoutVite()`.
- Suites: `tests/Unit/Services` (PenaltyService, CommissionService), `tests/Feature/Auth` (Login, Authorization), `tests/Feature/Alumnos`, `tests/Feature/Ventas`.
- Existen factories en `database/factories/` (Alumno, Sede, Producto, Venta, Membresia, MetodoPago, Categoria, User).
- **Estado**: parte de las suites falla por constraints de FKs en factories (CommissionServiceTest, VentaTest, AuthorizationTest). Los unit tests de PenaltyService y los de Login/Alumnos pasan.

## Convenciones

- Todo en español (UI, validaciones, mensajes, nombres de dominio).
- Formularios de crear/editar **siempre en modal** (`x-modal-form`), nunca vistas separadas.
- Vistas responsive (mobile-first), tablas con `overflow-x-auto`, modales con `max-h-96 overflow-y-auto`.
- Badges de estado: success=verde, warning=amarillo, danger=rojo, info=azul.
- Operaciones críticas (ventas, pagos, cierres) con `DB::transaction()`.
- Auditoría de operaciones críticas con `AuditService`.

## Gotchas

- **No confundir** `membresias` (catálogo) con `membresias_alumno` (historial por alumno).
- `SESSION_LIFETIME=30` (sesión expira en 30 min) y login con `throttle:5,1`.
- Cola por defecto `QUEUE_CONNECTION=database`; comandos de notificaciones agendados en `routes/console.php` (07:00 generar, 06:00 expirar).
- Vistas legacy sin ruta activa que se pueden ignorar: `resources/views/reporte/` y `resources/views/masivo/`.