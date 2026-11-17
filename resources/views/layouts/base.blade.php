<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'VoluntMatch — Tu talento tiene un lugar donde hacer match')</title>
    <script>
        try {
            var storedTheme = localStorage.getItem('voluntmatch-theme');
            if (storedTheme === 'dark' || (!storedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
        // Permite ocultar los pasos con CSS solo cuando JavaScript está activo:
        // sin JS ambos pasos quedan visibles y el formulario sigue siendo usable.
        document.documentElement.classList.add('js');
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    {{-- JS dividido por responsabilidad, sin build: se cargan en orden con defer.
         El orden importa: toasts.js declara showToast (global) que usan los demas. --}}
    <script src="{{ asset('js/site/theme.js') }}" defer></script>
    <script src="{{ asset('js/site/menu.js') }}" defer></script>
    <script src="{{ asset('js/site/reveal.js') }}" defer></script>
    <script src="{{ asset('js/site/cursor.js') }}" defer></script>
    <script src="{{ asset('js/site/hero.js') }}" defer></script>
    <script src="{{ asset('js/site/toasts.js') }}" defer></script>
    <script src="{{ asset('js/site/form-steps.js') }}" defer></script>
    <script src="{{ asset('js/site/registro-ong.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/proyecto-modal.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/voluntario.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/chat.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/talento.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/admin.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/proyectos.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/inbox.js') }}" defer></script>
    <script src="{{ asset('js/dashboard/metricas.js') }}" defer></script>
</head>
<body>
    <div class="app-shell">
        @yield('content')
    </div>

    {{-- Registro de ONG en ventana: disponible en cualquier página pública. --}}
    @guest
        @unless (request()->routeIs('registro-organizacion'))
            @include('partials.registro-ong-modal')
        @endunless
    @endguest
</body>
</html>
