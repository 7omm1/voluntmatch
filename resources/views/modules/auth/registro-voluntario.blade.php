@extends('layouts.base')

@section('title', 'Registro de estudiante — VoluntMatch')

@php
    $camposOpcionales = ['dni', 'phone', 'region', 'city', 'university', 'career', 'cycle', 'availability', 'interests'];
    $abrirPaso2 = $errors->any() && collect($camposOpcionales)->contains(fn ($campo) => $errors->has($campo));
@endphp

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
                    <p class="eyebrow dark-text">TU COMUNIDAD TE ESPERA</p>
                    <h2>Empieza a crear impacto.</h2>
                </div>

                <div class="role-tabs" aria-label="Seleccionar tipo de usuario">
                    <button type="button" class="active">Estudiante</button>
                    <a href="{{ route('registro-organizacion') }}">ONG</a>
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

                <form method="POST" action="{{ route('registrar-voluntario') }}" data-form-steps
                    data-start="{{ $abrirPaso2 ? 2 : 1 }}">
                    @csrf

                    <div class="step-track">
                        <button type="button" class="step-dot active" data-step-jump="1">
                            <i>1</i> <span>Tu cuenta</span>
                        </button>
                        <button type="button" class="step-dot" data-step-jump="2">
                            <i>2</i> <span>Tu perfil <small>(opcional)</small></span>
                        </button>
                    </div>

                    <section class="form-step" data-step="1">
                        <div class="form-grid">
                            <label>
                                Nombre completo
                                <input type="text" name="name" required placeholder="María González"
                                    value="{{ old('name') }}" />
                            </label>
                            <label>
                                Correo electrónico
                                <input type="email" name="email" required placeholder="tu@email.com"
                                    value="{{ old('email') }}" />
                            </label>
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

                        <button class="jelly-button wide" type="button" data-step-next>
                            Continuar <x-icon name="arrow" />
                        </button>
                        <p class="form-note">Tu cuenta en 30 segundos. El perfil lo completas cuando quieras.</p>
                    </section>

                    <section class="form-step" data-step="2" hidden>
                        <p class="form-hint">
                            <x-icon name="spark" :size="14" />
                            Opcionales, pero son los datos con los que calculamos tu porcentaje de perfil y
                            habilitamos el match con una ONG. Puedes completarlos ahora o después desde tu panel.
                        </p>

                        <p class="form-section-title">Datos personales</p>
                        <div class="form-grid">
                            <label>
                                DNI <small>(opcional)</small>
                                <input type="text" name="dni" inputmode="numeric" maxlength="8" pattern="\d{8}"
                                    placeholder="8 dígitos" value="{{ old('dni') }}" />
                            </label>
                            <label>
                                Celular (WhatsApp) <small>(opcional)</small>
                                <input type="tel" name="phone" inputmode="numeric" maxlength="9" pattern="9\d{8}"
                                    placeholder="987654321" value="{{ old('phone') }}" />
                            </label>
                            <label>
                                Región <small>(opcional)</small>
                                <select name="region">
                                    <option value="">Selecciona tu región</option>
                                    @foreach ($regiones as $region)
                                        <option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                Ciudad / distrito <small>(opcional)</small>
                                <input type="text" name="city" placeholder="Ej. San Juan de Lurigancho"
                                    value="{{ old('city') }}" />
                            </label>
                        </div>

                        <p class="form-section-title">Estudios</p>
                        <div class="form-grid">
                            <label>
                                Universidad / instituto <small>(opcional)</small>
                                <input type="text" name="university" list="universidades-pe"
                                    placeholder="Elige o escribe tu casa de estudios" value="{{ old('university') }}" />
                                <datalist id="universidades-pe">
                                    @foreach ($universidades as $universidad)
                                        <option value="{{ $universidad }}"></option>
                                    @endforeach
                                </datalist>
                            </label>
                            <label>
                                Carrera profesional <small>(opcional)</small>
                                <input type="text" name="career" placeholder="Ej. Ingeniería Ambiental"
                                    value="{{ old('career') }}" />
                            </label>
                            <label>
                                Ciclo / grado <small>(opcional)</small>
                                <select name="cycle">
                                    <option value="">Selecciona</option>
                                    @foreach ($ciclos as $ciclo)
                                        <option value="{{ $ciclo }}" @selected(old('cycle') === $ciclo)>{{ $ciclo }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                Disponibilidad <small>(opcional)</small>
                                <select name="availability">
                                    <option value="">Horas por semana</option>
                                    @foreach ($disponibilidades as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('availability') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <p class="form-section-title">Causas que te mueven <small>(opcional, hasta 6)</small></p>
                        <div class="check-grid">
                            @foreach ($intereses as $interes)
                                <label class="check-row">
                                    <input type="checkbox" name="interests[]" value="{{ $interes }}"
                                        @checked(in_array($interes, old('interests', []), true)) />
                                    <span>{{ $interes }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="step-actions">
                            <button class="ghost-button" type="button" data-step-prev>
                                <x-icon name="arrow" /> Volver
                            </button>
                            <button class="jelly-button" type="submit">
                                Crear mi cuenta <x-icon name="check" />
                            </button>
                        </div>
                    </section>
                </form>

                <a class="auth-back" href="{{ route('login') }}">← Volver a iniciar sesión</a>
            </div>
        </div>
    </div>
@endsection
