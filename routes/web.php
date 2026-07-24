<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProductoController;
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

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home.index');

    Route::resource('alumnos', AlumnoController::class);
    Route::resource('asistencias', AsistenciaController::class);
    Route::resource('membresias', MembresiaController::class);
    Route::resource('pagos', PagoController::class);
    Route::resource('productos', ProductoController::class);
    Route::resource('ventas', VentaController::class);
    Route::resource('gastos', GastoController::class);
    Route::resource('usuarios', UsuarioController::class);

    Route::get('/pagos-completos', [PagoController::class, 'completos'])->name('pagos.completos');
    Route::get('/pagos-incompletos', [PagoController::class, 'incompletos'])->name('pagos.incompletos');

    Route::get('/ventas-reservados', [VentaController::class, 'reservados'])->name('ventas.reservados');

    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/apertura', [CajaController::class, 'apertura'])->name('caja.apertura');
    Route::post('/caja/cierre/{caja}', [CajaController::class, 'cierre'])->name('caja.cierre');

    Route::get('/reportes', [DashboardController::class, 'reportes'])->name('reportes.index');
    Route::get('/reportes/ventas', [DashboardController::class, 'reportesVentas'])->name('reportes.ventas');
    Route::get('/reportes/formulario', [DashboardController::class, 'formulario'])->name('reportes.formulario');

    Route::get('/graficos', [DashboardController::class, 'graficos'])->name('graficos.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
});
