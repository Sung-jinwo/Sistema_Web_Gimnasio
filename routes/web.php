<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AsistenciaPublicController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\CongelamientoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReporteAlumnoController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->middleware(['guest', 'throttle:5,1']);

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Un GET a /logout (URL directa, historial, doble clic con sesión expirada)
// no debe reventar con 405: redirige al login. Nunca cierra sesión por GET.
Route::get('/logout', function () {
    if (auth()->check()) {
        return redirect()->route('home.index');
    }

    return redirect()->route('login');
})->name('logout.get');

// Rutas de recuperación de contraseña (públicas)
Route::get('/forgot-password', [PasswordController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordController::class, 'update'])->name('password.update');

Route::get('/asistencia', [AsistenciaPublicController::class, 'index'])->name('asistencia.publica');
Route::post('/asistencia', [AsistenciaPublicController::class, 'store'])->name('asistencia.publica.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::middleware(['permission:alumnos.ver'])->group(function () {
        Route::resource('alumnos', AlumnoController::class);
    });

    Route::middleware(['permission:alumnos.reportar'])->group(function () {
        Route::get('/alumnos-reporte-mensual', [ReporteAlumnoController::class, 'index'])->name('alumnos.reporte');
        Route::get('/alumnos-reporte-mensual/exportar', [ReporteAlumnoController::class, 'exportar'])->name('alumnos.reporte.exportar');
    });

    Route::get('/asistencias', [AsistenciaController::class, 'index'])
        ->middleware('permission:asistencias.ver')->name('asistencias.index');
    Route::post('/asistencias', [AsistenciaController::class, 'store'])
        ->middleware('permission:asistencias.crear')->name('asistencias.store');

    Route::middleware(['permission:membresias.ver'])->group(function () {
        Route::get('/membresias', [MembresiaController::class, 'index'])->name('membresias.index');
        Route::get('/alumnos/{alumno}/membresias/historial', [MembresiaController::class, 'historial'])->name('membresias.historial');
    });
    Route::post('/membresias', [MembresiaController::class, 'store'])->middleware('permission:membresias.crear')->name('membresias.store');
    Route::get('/membresias/{membresia}/edit', [MembresiaController::class, 'edit'])->middleware('permission:membresias.editar')->name('membresias.edit');
    Route::match(['put', 'patch'], '/membresias/{membresia}', [MembresiaController::class, 'update'])->middleware('permission:membresias.editar')->name('membresias.update');
    Route::delete('/membresias/{membresia}', [MembresiaController::class, 'destroy'])->middleware('permission:membresias.editar')->name('membresias.destroy');

    Route::middleware(['permission:membresias.congelar'])->group(function () {
        Route::post('/membresias-alumno/{membresiaAlumno}/congelar', [CongelamientoController::class, 'programar'])->name('membresias.congelar');
        Route::post('/congelamientos/{congelamiento}/finalizar', [CongelamientoController::class, 'finalizar'])->name('congelamientos.finalizar');
        Route::post('/congelamientos/{congelamiento}/cancelar', [CongelamientoController::class, 'cancelar'])->name('congelamientos.cancelar');
    });
    Route::post('/membresias-alumno/{membresiaAlumno}/ajustar-vigencia', [CongelamientoController::class, 'ajustar'])
        ->middleware('permission:membresias.ajustar_vigencia')->name('membresias.ajustar-vigencia');

    Route::middleware(['permission:cobranza.ver'])->group(function () {
        Route::get('/cobranza', [CobranzaController::class, 'index'])->name('cobranza.index');
        Route::get('/cobranza/cuotas-vencidas', [CobranzaController::class, 'vencidas'])->name('cobranza.vencidas');
        Route::get('/cobranza/historial', [CobranzaController::class, 'historial'])->name('cobranza.historial');
        Route::post('/cobranza/ventas/{venta}/abonos', [CobranzaController::class, 'abonar'])
            ->middleware('permission:cobranza.abonar')->name('cobranza.abonar');
    });

    Route::redirect('/pagos', '/cobranza')->name('pagos.index');
    Route::redirect('/pagos-completos', '/ventas?estado_pago=pagado')->name('pagos.completos');
    Route::redirect('/pagos-incompletos', '/cobranza')->name('pagos.incompletos');
    Route::redirect('/cuotas-vencidas', '/cobranza/cuotas-vencidas')->name('pagos.cuotas.vencidas');

    Route::middleware(['permission:productos.ver'])->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::get('/productos/{producto}', [ProductoController::class, 'show'])->whereNumber('producto')->name('productos.show');
        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    });
    Route::middleware(['permission:productos.crear'])->group(function () {
        Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    });
    Route::middleware(['permission:productos.editar'])->group(function () {
        Route::get('/productos/{producto}/edit', [ProductoController::class, 'edit'])->whereNumber('producto')->name('productos.edit');
        Route::match(['put', 'patch'], '/productos/{producto}', [ProductoController::class, 'update'])->whereNumber('producto')->name('productos.update');
        Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->whereNumber('producto')->name('productos.destroy');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::post('/categorias/{categoria}/toggle', [CategoriaController::class, 'toggle'])->name('categorias.toggle');
    });

    Route::middleware(['permission:ventas.ver'])->group(function () {
        Route::resource('ventas', VentaController::class)->only(['index', 'store', 'destroy']);
        Route::get('/ventas/datos/rapida', [VentaController::class, 'datosVentaRapida'])->name('ventas.datos.rapida');
        Route::get('/ventas/datos/producto', [VentaController::class, 'datosVentaProducto'])->name('ventas.datos.producto');
        Route::get('/ventas/datos/membresia', [VentaController::class, 'datosVentaMembresia'])->name('ventas.datos.membresia');
        Route::get('/ventas/alumnos/buscar', [VentaController::class, 'buscarAlumnos'])->name('ventas.alumnos.buscar');
        Route::get('/ventas/productos/buscar', [VentaController::class, 'buscarProductos'])->name('ventas.productos.buscar');
        Route::get('/ventas/membresias/buscar', [VentaController::class, 'buscarMembresias'])->name('ventas.membresias.buscar');
        Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    });

    Route::middleware(['permission:gastos.ver'])->group(function () {
        Route::resource('gastos', GastoController::class)->except(['create', 'show']);
        Route::post('/gastos/{gasto}/aprobar', [GastoController::class, 'aprobar'])->name('gastos.aprobar');
        Route::post('/gastos/{gasto}/rechazar', [GastoController::class, 'rechazar'])->name('gastos.rechazar');
    });

    Route::middleware(['permission:comisiones.ver'])->group(function () {
        Route::get('/comisiones', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::get('/comisiones/mis-comisiones', [ComisionController::class, 'misComisiones'])->name('comisiones.mis-comisiones');
        Route::get('/comisiones/{comision}', [ComisionController::class, 'show'])->name('comisiones.show');
    });

    Route::middleware(['permission:comisiones.aprobar'])->group(function () {
        Route::post('/comisiones/{comision}/aprobar', [ComisionController::class, 'aprobar'])->name('comisiones.aprobar');
        Route::post('/comisiones/{comision}/observar', [ComisionController::class, 'observar'])->name('comisiones.observar');
        Route::post('/comisiones/aprobar-seleccion', [ComisionController::class, 'aprobarSeleccion'])->name('comisiones.aprobar-seleccion');
    });

    Route::middleware(['permission:comisiones.liquidar'])->group(function () {
        Route::post('/comisiones/{comision}/liquidar', [ComisionController::class, 'liquidar'])->name('comisiones.liquidar');
        Route::post('/comisiones/liquidar-seleccion', [ComisionController::class, 'liquidarSeleccion'])->name('comisiones.liquidar-seleccion');
    });

    Route::middleware(['permission:caja.ver'])->group(function () {
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/caja/apertura', [CajaController::class, 'apertura'])->name('caja.apertura');
        Route::post('/caja/cierre/{caja}', [CajaController::class, 'cierre'])->name('caja.cierre');
        Route::post('/caja/{caja}/aprobar', [CajaController::class, 'aprobar'])->name('caja.aprobar');
        Route::post('/caja/{caja}/observar', [CajaController::class, 'observar'])->name('caja.observar');
        Route::get('/caja/{caja}/pdf', [CajaController::class, 'pdf'])->name('caja.pdf');
        Route::post('/caja/{caja}/anular', [CajaController::class, 'anular'])->name('caja.anular');
    });

    Route::middleware(['permission:reportes.ver'])->group(function () {
        Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/ventas', [ReportController::class, 'ventas'])->name('reportes.ventas');
        Route::get('/reportes/membresias', [ReportController::class, 'membresias'])->name('reportes.membresias');
        Route::get('/reportes/productos', [ReportController::class, 'productos'])->name('reportes.productos');
        Route::get('/reportes/comisiones', [ReportController::class, 'comisiones'])->name('reportes.comisiones');
        Route::get('/reportes/gastos', [ReportController::class, 'gastos'])->name('reportes.gastos');
        Route::get('/reportes/caja', [ReportController::class, 'caja'])->name('reportes.caja');
        Route::get('/reportes/vencimientos', [ReportController::class, 'vencimientos'])->name('reportes.vencimientos');
    });

    Route::middleware(['permission:seguimiento.ver'])->group(function () {
        Route::get('/seguimiento', [SeguimientoController::class, 'index'])->name('seguimiento.index');
        Route::get('/seguimiento/vencimientos', [SeguimientoController::class, 'vencimientos'])->name('seguimiento.vencimientos');
        Route::get('/seguimiento/vencidos', [SeguimientoController::class, 'vencidos'])->name('seguimiento.vencidos');
        Route::get('/seguimiento/whatsapp/{alumno}', [SeguimientoController::class, 'whatsapp'])->name('seguimiento.whatsapp');
    });

    Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/leida', [NotificationController::class, 'marcarLeida'])->name('leida');
        Route::post('/marcar-todas', [NotificationController::class, 'marcarTodasLeidas'])->name('marcar-todas');
        Route::get('/no-leidas', [NotificationController::class, 'noLeidas'])->name('no-leidas');
    });

    Route::middleware(['permission:usuarios.ver'])->group(function () {
        Route::resource('usuarios', UsuarioController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::post('/usuarios/{usuario}/toggle', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggle');
    });

    Route::middleware(['permission:sedes.ver'])->group(function () {
        Route::get('/sedes', [SedeController::class, 'index'])->name('sedes.index');
        Route::post('/sedes', [SedeController::class, 'store'])->name('sedes.store');
        Route::put('/sedes/{sede}', [SedeController::class, 'update'])->name('sedes.update');
        Route::get('/sedes/{sede}/edit', [SedeController::class, 'edit'])->name('sedes.edit');
        Route::post('/sedes/{sede}/toggle', [SedeController::class, 'toggleEstado'])->name('sedes.toggle');
    });

    Route::middleware(['permission:auditoria.ver'])->group(function () {
        Route::get('/auditoria', [AuditController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/{log}', [AuditController::class, 'show'])->name('auditoria.show');
    });

    // Rutas de cambio de contraseña (autenticadas)
    Route::get('/perfil/cambiar-password', [PasswordController::class, 'showChangePassword'])->name('password.change.form');
    Route::put('/perfil/cambiar-password', [PasswordController::class, 'changePassword'])->name('password.change');

    Route::redirect('/graficos', '/dashboard')->name('graficos.index');
});
