<?php

use App\Http\Controllers\Admin\ActividadController as AdminActividadController;
use App\Http\Controllers\Admin\AnaliticaController as AdminAnaliticaController;
use App\Http\Controllers\Admin\AnuncioController as AdminAnuncioController;
use App\Http\Controllers\Admin\BusquedaController as AdminBusquedaController;
use App\Http\Controllers\Admin\ConfiguracionController as AdminConfiguracionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OportunidadController as AdminOportunidadController;
use App\Http\Controllers\Admin\OrganizacionController as AdminOrganizacionController;
use App\Http\Controllers\Admin\ReporteController as AdminReporteController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
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
    Route::get('/dashboard-admin', [AdminDashboardController::class, 'index'])->middleware('role:admin')->name('dashboard-admin');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/postular', [PostulacionController::class, 'guardar'])->middleware('role:voluntario')->name('postular');
    Route::post('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::post('/perfil/skills', [PerfilController::class, 'skills'])->name('perfil.skills');
});

// Panel de administracion: cada modulo tiene su propia ruta y accion.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard-admin'))->name('home');
    Route::get('/buscar', [AdminBusquedaController::class, 'index'])->name('buscar');

    // Usuarios y moderacion de cuentas.
    Route::get('/usuarios', [AdminUsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/exportar', [AdminUsuarioController::class, 'exportar'])->name('usuarios.exportar');
    Route::get('/usuarios/{user}', [AdminUsuarioController::class, 'show'])->name('usuarios.show');
    Route::patch('/usuarios/{user}/bloquear', [AdminUsuarioController::class, 'bloquear'])->name('usuarios.bloquear');
    Route::patch('/usuarios/{user}/reactivar', [AdminUsuarioController::class, 'reactivar'])->name('usuarios.reactivar');
    Route::patch('/usuarios/{user}/rol', [AdminUsuarioController::class, 'cambiarRol'])->name('usuarios.rol');
    Route::patch('/usuarios/{user}/notas', [AdminUsuarioController::class, 'notas'])->name('usuarios.notas');
    Route::post('/usuarios/{user}/reportes', [AdminReporteController::class, 'store'])->name('reportes.store');

    // Verificacion de ONG.
    Route::get('/organizaciones', [AdminOrganizacionController::class, 'index'])->name('organizaciones.index');
    Route::get('/organizaciones/exportar', [AdminOrganizacionController::class, 'exportar'])->name('organizaciones.exportar');
    Route::patch('/organizaciones/{organizacion}/revisar', [AdminOrganizacionController::class, 'revisar'])->name('organizaciones.revisar');

    // Curaduria de oportunidades.
    Route::get('/oportunidades', [AdminOportunidadController::class, 'index'])->name('oportunidades.index');
    Route::get('/oportunidades/exportar', [AdminOportunidadController::class, 'exportar'])->name('oportunidades.exportar');
    Route::patch('/oportunidades/{oportunidad}/estado', [AdminOportunidadController::class, 'cambiarEstado'])->name('oportunidades.estado');
    Route::patch('/oportunidades/{oportunidad}/destacar', [AdminOportunidadController::class, 'destacar'])->name('oportunidades.destacar');
    Route::delete('/oportunidades/{oportunidad}', [AdminOportunidadController::class, 'destroy'])->name('oportunidades.destroy');

    // Reportes de moderacion.
    Route::get('/reportes', [AdminReporteController::class, 'index'])->name('reportes.index');
    Route::patch('/reportes/{reporte}/resolver', [AdminReporteController::class, 'resolver'])->name('reportes.resolver');
    Route::patch('/reportes/{reporte}/descartar', [AdminReporteController::class, 'descartar'])->name('reportes.descartar');

    // Soporte.
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [AdminTicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/{ticket}/responder', [AdminTicketController::class, 'responder'])->name('tickets.responder');
    Route::patch('/tickets/{ticket}/cerrar', [AdminTicketController::class, 'cerrar'])->name('tickets.cerrar');

    // Anuncios internos.
    Route::get('/anuncios', [AdminAnuncioController::class, 'index'])->name('anuncios.index');
    Route::post('/anuncios', [AdminAnuncioController::class, 'store'])->name('anuncios.store');
    Route::patch('/anuncios/{anuncio}/toggle', [AdminAnuncioController::class, 'toggle'])->name('anuncios.toggle');
    Route::delete('/anuncios/{anuncio}', [AdminAnuncioController::class, 'destroy'])->name('anuncios.destroy');

    // Analiticas, configuracion y auditoria.
    Route::get('/analiticas', [AdminAnaliticaController::class, 'index'])->name('analiticas');
    Route::get('/configuracion', [AdminConfiguracionController::class, 'index'])->name('configuracion');
    Route::patch('/configuracion', [AdminConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::get('/actividad', [AdminActividadController::class, 'index'])->name('actividad');
});
