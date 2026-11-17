<?php

namespace App\Providers;

use App\Models\Organizacion;
use App\Models\Reporte;
use App\Models\Ticket;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Anti fuerza bruta en el login: maximo 5 intentos por minuto
        // por combinacion de correo + IP. La sesion se regenera al
        // autenticar (ver AuthController::logear).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        // Contadores de pendientes para la barra lateral del panel admin.
        View::composer('layouts.admin', function ($view) {
            $view->with('adminPendientes', [
                'organizaciones' => Organizacion::pendientes()->count(),
                'reportes' => Reporte::pendientes()->count(),
                'tickets' => Ticket::abiertos()->count(),
            ]);
        });
    }
}
