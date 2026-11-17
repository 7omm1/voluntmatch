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
                    <h2>El talento que tu causa necesita.</h2>
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

                @if ($errors->any())
                    <div class="form-error form-errors" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('registrar-organizacion') }}">
                    @csrf

                    <p class="form-section-title">Datos de la organización</p>
                    <div class="form-grid">
                        <label>
                            Nombre de la organización
                            <input type="text" name="name" required placeholder="Fundación Horizonte"
                                value="{{ old('name') }}" />
                        </label>
                        <label>
                            RUC
                            <input type="text" name="ruc" required inputmode="numeric" maxlength="11"
                                pattern="(10|15|17|20)\d{9}" placeholder="11 dígitos" value="{{ old('ruc') }}" />
                        </label>
                        <label>
                            Tipo de organización
                            <select name="org_type" required>
                                <option value="">Selecciona</option>
                                @foreach ($tipos as $tipo)
                                    <option value="{{ $tipo }}" @selected(old('org_type') === $tipo)>{{ $tipo }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Representante legal
                            <input type="text" name="legal_rep" required placeholder="Nombres y apellidos"
                                value="{{ old('legal_rep') }}" />
                        </label>
                        <label>
                            Celular de contacto
                            <input type="tel" name="phone" required inputmode="numeric" maxlength="9"
                                pattern="9\d{8}" placeholder="987654321" value="{{ old('phone') }}" />
                        </label>
                        <label>
                            Correo institucional
                            <input type="email" name="email" required placeholder="contacto@organizacion.org"
                                value="{{ old('email') }}" />
                        </label>
                        <label>
                            Región
                            <select name="region" required>
                                <option value="">Selecciona la región</option>
                                @foreach ($regiones as $region)
                                    <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            Ciudad / distrito
                            <input type="text" name="city" required placeholder="Ej. Miraflores"
                                value="{{ old('city') }}" />
                        </label>
                        <label class="form-span-2">
                            Dirección (sede principal)
                            <input type="text" name="address" required placeholder="Av. Ejemplo 123, oficina 4"
                                value="{{ old('address') }}" />
                        </label>
                        <label>
                            Sitio web <small>(opcional)</small>
                            <input type="url" name="website" placeholder="https://mi-ong.org"
                                value="{{ old('website') }}" />
                        </label>
                        <label>
                            Causa / sector principal
                            <select name="sector" required>
                                <option value="">Selecciona</option>
                                @foreach ($sectores as $sector)
                                    <option value="{{ $sector }}" @selected(old('sector') === $sector)>{{ $sector }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label>
                        Misión de la organización
                        <textarea name="mission" rows="3" required maxlength="1000"
                            placeholder="Cuéntanos qué hace tu organización y a quién ayuda (mínimo 30 caracteres).">{{ old('mission') }}</textarea>
                    </label>

                    <p class="form-section-title">Seguridad de la cuenta</p>
                    <div class="form-grid">
                        <label>
                            Contraseña
                            <input type="password" name="password" required minlength="8"
                                placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
                        </label>
                        <label>
                            Repetir contraseña
                            <input type="password" name="password_confirmation" required minlength="8"
                                placeholder="Repite la contraseña" autocomplete="new-password" />
                        </label>
                    </div>

                    <label class="check-row check-terms">
                        <input type="checkbox" name="terms" value="1" required @checked(old('terms')) />
                        <span>Acepto los <a href="{{ route('privacidad') }}">términos y la política de privacidad</a> de VoluntMatch.</span>
                    </label>

                    <button class="jelly-button wide" type="submit">
                        Registrar mi organización <x-icon name="arrow" />
                    </button>
                </form>

                <p class="form-note">
                    Verificaremos el RUC de tu organización antes de publicar tus proyectos.
                </p>
                <a class="auth-back" href="{{ route('login') }}">← Volver a iniciar sesión</a>
            </div>
        </div>
    </div>
@endsection