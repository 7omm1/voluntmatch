<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OportunidadController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PostulacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Páginas Públicas
Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/contacto', function () {
    return view('contacto');
})->name('contacto');

Route::get('/politica-privacidad', function () {
    return view('pages/privacidad');
})->name('privacidad');

Route::post('/contacto', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'message' => 'required|string|max:5000',
    ]);

    // TODO: enviar por correo o almacenar el mensaje.
    return back()->with('success', '¡Mensaje enviado! Te responderemos muy pronto.');
})->name('contacto.enviar');

Route::get('/oportunidades', [OportunidadController::class, 'index'])->name('oportunidades');
Route::get('/detalle-oportunidad/{oportunidad:slug}', [OportunidadController::class, 'show'])->name('detalle-oportunidad');

// Rutas de Autenticación (Solo Invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::get('/registro-voluntario', [AuthController::class, 'registroVoluntario'])->name('registro-voluntario');
    Route::get('/registro-organizacion', [AuthController::class, 'registroOrganizacion'])->name('registro-organizacion');
    // Cada formulario envía a su propia ruta: el rol lo fija el controlador
    // según la ruta y nunca se confía en un campo del formulario.
    Route::post('/registro-voluntario', [AuthController::class, 'registrarVoluntario'])->name('registrar-voluntario');
    Route::post('/registro-organizacion', [AuthController::class, 'registrarOrganizacion'])->name('registrar-organizacion');
    Route::post('/logear', [AuthController::class, 'logear'])->middleware('throttle:login')->name('logear');
});

// Rutas Protegidas (Solo Usuarios Autenticados)
Route::middleware('auth')->group(function () {
    Route::get('/home', [AuthController::class, 'home'])->name('home');
    Route::get('/dashboard-organizacion', [DashboardController::class, 'index'])->middleware('role:organizacion')->name('dashboard-organizacion');
    Route::get('/dashboard-admin', [DashboardController::class, 'admin'])->middleware('role:admin')->name('dashboard-admin');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/postular', [PostulacionController::class, 'guardar'])->middleware('role:voluntario')->name('postular');
    Route::post('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::post('/perfil/skills', [PerfilController::class, 'skills'])->name('perfil.skills');
});
