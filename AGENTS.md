# AGENTS.md

## Project

SIGG — Sistema Integral de Gestion para Gimnasios. Laravel 12 + PHP 8.2, Vite 7 + Tailwind CSS 4, Alpine.js, Blade. Spanish-language domain throughout.

## Commands

```bash
composer setup          # Full install: composer install, .env, key:generate, migrate, npm install, npm run build
composer dev            # Runs server + queue:listen + pail (logs) + vite concurrently
composer test           # Clears config cache then runs php artisan test (PHPUnit, SQLite in-memory)
./vendor/bin/pint       # Lint/format PHP (Laravel Pint)
npm run build           # Build frontend assets with Vite
npm run dev             # Vite dev server (HMR)
```

Run a single test: `php artisan test --filter=TestClassName` or `php artisan test tests/Feature/SomeTest.php`

## Architecture

- **Routes**: `routes/web.php` — all domain routes are inside `auth` middleware group. Resource controllers for all CRUD modules.
- **Models** (`app/Models/`): Alumno, Asistencia, Caja, Categoria, CategoriaGasto, Comision, DetalleVenta, Gasto, Membresia, MetodoPago, MovimientoCaja, Padre, Pago, PagoDetalle, Producto, Sede, User, Venta
- **Controllers** (`app/Http/Controllers/`): AlumnoController, AsistenciaController, MembresiaController, PagoController, ProductoController, VentaController, GastoController, CajaController, UsuarioController, DashboardController, Auth/LoginController
- **Form Requests** (`app/Http/Requests/`): AlumnoRequest, AsistenciaRequest, MembresiaRequest, PagoRequest, ProductoRequest, VentaRequest, GastoRequest, UsuarioRequest
- **Views** (`resources/views/`): Blade templates organized by domain folder. Use existing Blade components: `x-button`, `x-badge`, `x-table`, `x-modal`, `x-modal-form`, `x-search-input`, `x-select-filter`, `x-toast-notifications`, `x-page-loader`
- **Frontend**: Tailwind CSS 4 (compiled via Vite), Alpine.js (bundled), Font Awesome 6 (CDN), Inter font (Google Fonts CDN)
- **Color scheme**: Primary `pink-600` (#E84B7A), secondary `gray-900`, neutral `gray-50`

## Database

- Default connection: **SQLite** (file: `database/database.sqlite`)
- Migrations in `database/migrations/` define ALL tables (infrastructure + domain)
- Domain migration: `0001_01_01_000003_create_domain_tables.php`
- **Spanish column names** with prefixes: `alum_nombre`, `fksede`, `fkalum`, `fksexo`
- **Custom primary keys**: `id_alumno`, `id_pag`, `id_productos`, etc. — always set `$primaryKey`
- **Custom foreign keys**: `fkalum`, `fksede`, `fkuser`, `fkalumno`, `fkmetodo`, etc. — always specify in relationships
- Domain models use `$guarded = []` (mass assignment open). Only `User` uses `$fillable`
- Alumno uses `SoftDeletes`
- spatie/laravel-permission migration exists at `2026_07_24_193509_create_permission_tables.php`

## Code Conventions

- Controllers use standard `XxxController.php` naming
- All user-facing text and domain terms are in **Spanish**
- Controllers support both standard and AJAX responses (`$request->expectsJson()`)
- Auth uses custom `LoginController` with session-based auth (no Sanctum)
- Roles: integer-based (0=Admin, 1=Empleado, 2=Asistencia, 3=Ventas) in `User` model constants
- Sidebar uses `canAccessMenuItem()` for role-based menu filtering
- Session expiry: 30 minutes (`SESSION_LIFETIME=30` in `.env`)
- Login has rate limiting: `throttle:5,1`

## Packages

- `spatie/laravel-permission` v6 — roles/permissions (config at `config/permission.php`)
- `maatwebsite/excel` — Excel export
- `barryvdh/laravel-dompdf` — PDF generation
- `laravel/pint` — PHP formatting
- `laravel/pail` — log tailing

## Environment

- Runs on **Laragon** (Windows). Server: `php artisan serve` or Laragon's Apache/Nginx.
- `.env.example` defaults to SQLite; MySQL config is commented out
- No API routes file — server-rendered Blade app
- Tests use `withoutVite()` in `TestCase::setUp()` to avoid requiring built assets
