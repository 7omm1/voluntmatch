@extends('layouts.base')

@section('title', 'Iniciar sesión — VoluntMatch')

@section('content')
    <div class="page-panel">
        <div class="panel-ambient" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="panel-topbar">
            <a class="brand" href="{{ route('index') }}">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </a>
            <a class="panel-close" href="{{ route('index') }}">
                <x-icon name="close" /> Cerrar
            </a>
        </div>

        <div class="auth-page">
            <div class="auth-modal">
                <div class="auth-heading">
                    <span class="mini-mark"><x-icon name="heart" :size="18" /></span>
                    <p class="eyebrow dark-text">TU COMUNIDAD TE ESPERA</p>
                    <h2>Volvamos a hacer el bien.</h2>
                </div>

                <div class="auth-mode-tabs">
                    <button type="button" class="active">Iniciar sesión</button>
                    <a href="{{ route('registro-voluntario') }}">Registrarme</a>
                </div>

                @if (session('success'))
                    <div class="form-success" role="status">
                        <x-icon name="check" :size="16" /> {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="form-error" role="alert">
                        <x-icon name="close" :size="16" /> {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('logear') }}">
                    @csrf
                    <label>
                        Correo electrónico
                        <input type="email" name="email" required placeholder="tu@email.com"
                            value="{{ old('email') }}" />
                    </label>
                    <label>
                        Contraseña
                        <input type="password" name="password" required placeholder="Tu contraseña" />
                    </label>
                    <button class="jelly-button wide" type="submit">
                        Entrar a VoluntMatch <x-icon name="arrow" />
                    </button>
                </form>

                <p class="auth-ngo-invite">
                    ¿Tienes una ONG?
                    <a href="{{ route('registro-organizacion') }}" data-ngo-modal-open>Registra tu organización</a>
                </p>

                <p class="form-note">
                    Al continuar aceptas nuestros términos y nuestra
                    <a href="{{ route('privacidad') }}">política de privacidad</a>.
                </p>
            </div>
        </div>
    </div>
@endsection