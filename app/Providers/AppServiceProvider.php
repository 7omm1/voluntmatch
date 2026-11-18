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

        // El asistente tiene límite por cuenta (o IP si es visitante) para
        // evitar abuso de coste, bucles de herramientas y fuerza bruta.
        RateLimiter::for('chatbot', function (Request $request) {
            $clave = $request->user()
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();

            return [
                Limit::perMinute(max(1, (int) config('chatbot.rate_limits.per_minute', 12)))->by($clave),
                Limit::perDay(max(1, (int) config('chatbot.rate_limits.per_day', 120)))->by($clave),
            ];
        });

        // La API tiene su propio límite por cliente: un token ruidoso no
        // consume la cuota del widget web ni la de otros consumidores.
        RateLimiter::for('api', function (Request $request) {
            $client = $request->attributes->get('api_client');
            $clave = $client ? 'client:'.$client->id : 'ip:'.$request->ip();

            return [
                Limit::perMinute(max(1, (int) config('api.rate_limits.per_minute', 60)))->by($clave),
                Limit::perDay(max(1, (int) config('api.rate_limits.per_day', 2000)))->by($clave),
            ];
        });

        // Freno por IP antes de autenticar: protege contra fuerza bruta de
        // tokens sin castigar a los clientes legítimos entre sí.
        RateLimiter::for('api-ip', function (Request $request) {
            return Limit::perMinute(120)->by('ip:'.$request->ip());
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
