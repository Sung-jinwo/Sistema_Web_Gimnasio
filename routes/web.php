<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\Auth\LoginController;


// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->middleware('guest');
    // ->middleware(['guest', 'throttle:5,1']);  para colocar 5 intentos por minutos 

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('home.index');
    })->name('home.index');
    
    Route::resource('alumnos', AlumnoController::class);

    
});










Route::get('/registro', function () {
    return view('registro.index');
})->name('registro.index');

Route::get('/asistenciaa', function () {
    return view('asistencia.index');
})->name('asistencia.create');

Route::get('/asistencia', function () {
    return view('asistencia.index');
})->name('asistencia.index');

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard.index');

Route::get('/masivo', function () {
    return view('masivo.index');
})->name('masivo.index');

Route::get('/membresias', function () {
    return view('membresias.index');
})->name('membresias.index');

Route::get('/pagos', function () {
    return view('pagos.index');
})->name('pagos.index');

Route::get('/completos', function () {
    return view('completos.index');
})->name('pagos.completos');

Route::get('/incompletos', function () {
    return view('incompletos.index');
})->name('pagos.incompletos');

Route::get('/productos', function () {
    return view('productos.index');
})->name('productos.index');

Route::get('/reportes', function () {
    return view('reporte.index');
})->name('reportes.index');

Route::get('/reportess', function () {
    return view('ventas.index');
})->name('reportes.ventas');

Route::get('/formulario', function () {
    return view('formulario.index');
})->name('reportes.formulario');

Route::get('/usuarios', function () {
    return view('usuarios.index');
})->name('usuarios.index');

Route::get('/ventas', function () {
    return view('ventas.index');
})->name('ventas.index');

Route::get('/reservados', function () {
    return view('reservados.index');
})->name('ventas.reservados');

Route::get('/gastos', function () {
    return view('gastos.index');
})->name('gastos.index');


Route::get('/graficos', function () {
    return view('graficos.index');
})->name('graficos.index');
