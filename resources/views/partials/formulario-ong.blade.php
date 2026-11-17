@php
    // Formulario compartido por la página completa y la ventana (modal).
    $modal = $modal ?? false;
    $regiones = $regiones ?? \App\Support\Peru::REGIONES;
    $tipos = $tipos ?? \App\Support\Peru::TIPOS_ORGANIZACION;
    $sectores = $sectores ?? \App\Support\Peru::SECTORES;

    // Solo reutilizamos los valores antiguos si el último envío fue este
    // formulario (evita mezclar datos del registro de estudiantes).
    $mostrarOld = ! $modal || old('_modal') === 'ong';
    $valor = fn (string $campo) => $mostrarOld ? old($campo) : null;

    $erroresOng = $errors->getBag('ong');
    $camposPaso2 = ['email', 'phone', 'password', 'password_confirmation', 'terms'];
    $pasoInicial = $erroresOng->any() && collect($camposPaso2)->contains(fn ($campo) => $erroresOng->has($campo)) ? 2 : 1;
@endphp

<form method="POST" action="{{ route('registrar-organizacion') }}" data-form-steps
    data-start="{{ $pasoInicial }}" @if ($modal) data-ngo-form @endif>
    @csrf
    @if ($modal)
        <input type="hidden" name="_modal" value="ong" />
    @endif

    <div class="step-track" aria-label="Pasos del registro">
        <button type="button" class="step-dot {{ $pasoInicial === 1 ? 'active' : '' }}" data-step-jump="1">
            <i>1</i> <span>Tu organización</span>
        </button>
        <button type="button" class="step-dot {{ $pasoInicial === 2 ? 'active' : '' }}" data-step-jump="2">
            <i>2</i> <span>Tu cuenta</span>
        </button>
    </div>

    {{-- PASO 1: lo esencial de la organización --}}
    <section class="form-step {{ $pasoInicial === 1 ? 'active' : '' }}" data-step="1">
        <div class="form-grid">
            <label class="form-span-2">
                Nombre de la organización
                <input type="text" name="name" required autocomplete="organization"
                    placeholder="Fundación Horizonte" value="{{ $valor('name') }}"
                    @error('name', 'ong') aria-invalid="true" @enderror />
                @error('name', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                RUC
                <input type="text" name="ruc" required inputmode="numeric" maxlength="11"
                    pattern="(10|15|17|20)\d{9}" placeholder="11 dígitos" value="{{ $valor('ruc') }}"
                    aria-describedby="ruc-help" @error('ruc', 'ong') aria-invalid="true" @enderror />
                @error('ruc', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Causa / sector principal
                <select name="sector" required @error('sector', 'ong') aria-invalid="true" @enderror>
                    <option value="">Selecciona</option>
                    @foreach ($sectores as $sector)
                        <option value="{{ $sector }}" @selected($valor('sector') === $sector)>{{ $sector }}</option>
                    @endforeach
                </select>
                @error('sector', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Región
                <select name="region" required @error('region', 'ong') aria-invalid="true" @enderror>
                    <option value="">Selecciona la región</option>
                    @foreach ($regiones as $region)
                        <option value="{{ $region }}" @selected($valor('region') === $region)>{{ $region }}</option>
                    @endforeach
                </select>
                @error('region', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Ciudad / distrito <small>(opcional)</small>
                <input type="text" name="city" autocomplete="address-level2" placeholder="Ej. Miraflores"
                    value="{{ $valor('city') }}" />
                @error('city', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
        </div>

        {{-- Datos que pueden completarse después desde la ficha de la ONG --}}
        <details class="form-extra">
            <summary>
                <span>Completar más datos <small>(opcional)</small></span>
            </summary>
            <div class="form-extra-body form-grid">
                <label>
                    Tipo de organización
                    <select name="org_type">
                        <option value="">Selecciona</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo }}" @selected($valor('org_type') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Representante legal
                    <input type="text" name="legal_rep" placeholder="Nombres y apellidos"
                        value="{{ $valor('legal_rep') }}" />
                </label>
                <label class="form-span-2">
                    Dirección de la sede principal
                    <input type="text" name="address" placeholder="Av. Ejemplo 123, oficina 4"
                        value="{{ $valor('address') }}" />
                </label>
                <label class="form-span-2">
                    Sitio web
                    <input type="url" name="website" placeholder="https://mi-ong.org"
                        value="{{ $valor('website') }}" />
                    @error('website', 'ong')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>
                <label class="form-span-2">
                    Misión de la organización
                    <textarea name="mission" rows="3" maxlength="1000"
                        placeholder="Cuéntanos qué hace tu organización y a quién ayuda.">{{ $valor('mission') }}</textarea>
                    @error('mission', 'ong')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </details>

        <p class="form-hint" id="ruc-help">
            <x-icon name="check" :size="14" />
            Verificaremos el RUC antes de publicar tus proyectos.
        </p>

        <div class="step-actions">
            <button class="jelly-button wide" type="button" data-step-next>
                Continuar <x-icon name="arrow" />
            </button>
        </div>
    </section>

    {{-- PASO 2: la cuenta --}}
    <section class="form-step {{ $pasoInicial === 2 ? 'active' : '' }}" data-step="2">
        <div class="form-grid">
            <label>
                Correo institucional
                <input type="email" name="email" required autocomplete="email"
                    placeholder="contacto@organizacion.org" value="{{ $valor('email') }}"
                    @error('email', 'ong') aria-invalid="true" @enderror />
                @error('email', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Celular de contacto
                <input type="tel" name="phone" required inputmode="numeric" maxlength="9"
                    pattern="9\d{8}" placeholder="987654321" value="{{ $valor('phone') }}"
                    @error('phone', 'ong') aria-invalid="true" @enderror />
                @error('phone', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Contraseña
                <span class="password-field">
                    <input type="password" name="password" required minlength="8"
                        placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                        @error('password', 'ong') aria-invalid="true" @enderror />
                    <button type="button" class="password-toggle" data-password-toggle
                        aria-label="Mostrar contraseña">Ver</button>
                </span>
                @error('password', 'ong')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Repetir contraseña
                <span class="password-field">
                    <input type="password" name="password_confirmation" required minlength="8"
                        placeholder="Repite la contraseña" autocomplete="new-password" />
                    <button type="button" class="password-toggle" data-password-toggle
                        aria-label="Mostrar contraseña">Ver</button>
                </span>
            </label>
        </div>

        <label class="check-row check-terms">
            <input type="checkbox" name="terms" value="1" required @checked($valor('terms')) />
            <span>Acepto los <a href="{{ route('privacidad') }}">términos y la política de privacidad</a> de
                VoluntMatch.</span>
        </label>
        @error('terms', 'ong')
            <span class="field-error">{{ $message }}</span>
        @enderror

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
