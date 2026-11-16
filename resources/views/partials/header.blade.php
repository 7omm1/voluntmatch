<header class="site-header">
    <a class="brand" href="{{ route('index') }}" aria-label="VoluntMatch, inicio">
        <span class="brand-mark">
            <span></span>
            <span></span>
        </span>
        <span>VOLUNTMATCH</span>
    </a>

    <div class="nav-zone">
        <button class="nav-trigger" type="button">
            Explorar <x-icon name="chevron" :size="16" />
        </button>
        <nav class="desktop-nav" aria-label="Navegación principal">
            <a href="{{ route('oportunidades') }}">Oportunidades</a>
            <a href="{{ route('oportunidades', ['tab' => 'participate']) }}">Cómo participar</a>
            <a href="{{ route('oportunidades', ['tab' => 'organizations']) }}">Para ONG</a>
            <a href="{{ route('index') }}#impacto">Impacto</a>
        </nav>
        <div class="nav-mega">
            <span>DESCUBRE VOLUNTMATCH</span>
            <a href="{{ route('oportunidades', ['tab' => 'participate']) }}">
                <b>01</b> Cómo funciona <x-icon name="arrow" />
            </a>
            <a href="{{ route('oportunidades') }}">
                <b>02</b> Oportunidades abiertas <x-icon name="arrow" />
            </a>
            <a href="{{ route('oportunidades', ['tab' => 'organizations']) }}">
                <b>03</b> Soluciones para ONG <x-icon name="arrow" />
            </a>
        </div>
    </div>

    <div class="header-actions">
        <button class="theme-button" type="button" data-theme-toggle
            aria-label="Alternar modo oscuro">
            <span class="theme-icon-light"><x-icon name="moon" /></span>
            <span class="theme-icon-dark"><x-icon name="sun" /></span>
        </button>
        @auth
            <a class="jelly-button small" href="{{ route('home') }}">
                Mi espacio <x-icon name="users" :size="17" />
            </a>
        @else
            <a class="text-button" href="{{ route('login') }}">Iniciar sesión</a>
            <a class="jelly-button small" href="{{ route('registro-voluntario') }}">
                Crear cuenta <x-icon name="arrow" :size="17" />
            </a>
        @endauth
        <button class="mobile-menu-button" type="button" data-mobile-toggle aria-label="Abrir menú">
            <span class="icon-open"><x-icon name="menu" /></span>
            <span class="icon-close"><x-icon name="close" /></span>
        </button>
    </div>

    <nav class="mobile-nav" data-mobile-nav>
        <a href="{{ route('oportunidades') }}">Ver oportunidades</a>
        <a href="{{ route('oportunidades', ['tab' => 'participate']) }}">Cómo participar</a>
        <a href="{{ route('oportunidades', ['tab' => 'organizations']) }}">Para ONG</a>
        <a href="{{ auth()->check() ? route('home') : route('login') }}">
            {{ auth()->check() ? 'Mi espacio' : 'Iniciar sesión' }}
        </a>
    </nav>
</header>
