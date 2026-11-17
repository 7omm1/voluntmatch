@extends('layouts.base')

@section('title', 'Registro de ONG — VoluntMatch')

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
            <div class="auth-modal wide">
                <div class="auth-heading">
                    <span class="mini-mark"><x-icon name="heart" :size="18" /></span>
                    <p class="eyebrow dark-text">VOLUNTMATCH PARA ORGANIZACIONES</p>
                    <h2>Registra tu ONG en 2 minutos.</h2>
                    <p class="ngo-modal-sub">Solo lo esencial. Añade más datos únicamente si lo necesitas.</p>
                </div>

                <div class="role-tabs" aria-label="Seleccionar tipo de usuario">
                    <a href="{{ route('registro-voluntario') }}">Estudiante</a>
                    <button type="button" class="active">ONG</button>
                </div>

                @if (session('success'))
                    <div class="form-success" role="status">
                        <x-icon name="check" :size="16" /> {{ session('success') }}
                    </div>
                @endif

                @include('partials.formulario-ong', ['modal' => false])

                <a class="auth-back" href="{{ route('login') }}">← Volver a iniciar sesión</a>
            </div>
        </div>
    </div>
@endsection
