@php
    $regiones = \App\Support\Peru::REGIONES;
    $ciclos = \App\Support\Peru::CICLOS;
    $disponibilidades = \App\Support\Peru::DISPONIBILIDADES;
    $intereses = \App\Support\Peru::INTERESES;
    $universidades = \App\Support\Peru::UNIVERSIDADES;
    $seleccionados = old('interests', $user->interests ?? []);
@endphp

<div class="project-modal-backdrop" data-profile-modal hidden>
    <article class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
        <button type="button" class="modal-close" data-profile-close aria-label="Cerrar">
            <x-icon name="close" />
        </button>

        <div class="profile-modal-head">
            <span class="mini-mark"><x-icon name="users" :size="18" /></span>
            <div>
                <p>COMPLETA TU PERFIL</p>
                <h2 id="profile-modal-title">Haz que las ONG te encuentren.</h2>
                <span>Llevas <b data-profile-percent>{{ $porcentaje }}%</b> completado. Cada dato suma.</span>
            </div>
            <div class="profile-modal-ring">
                <strong data-profile-percent>{{ $porcentaje }}%</strong>
                <small>perfil</small>
            </div>
        </div>

        <form method="POST" action="{{ route('perfil.actualizar') }}" data-profile-form>
            @csrf
            <div class="profile-modal-grid">
                <label>
                    Nombre completo
                    <input type="text" name="name" required value="{{ old('name', $user->name) }}" />
                </label>
                <label>
                    DNI
                    <input type="text" name="dni" inputmode="numeric" maxlength="8" pattern="\d{8}"
                        placeholder="8 dígitos" value="{{ old('dni', $user->dni) }}" />
                </label>
                <label>
                    Celular (WhatsApp)
                    <input type="tel" name="phone" inputmode="numeric" maxlength="9" pattern="9\d{8}"
                        placeholder="987654321" value="{{ old('phone', $user->phone) }}" />
                </label>
                <label>
                    Región
                    <select name="region">
                        <option value="">Selecciona tu región</option>
                        @foreach ($regiones as $region)
                            <option value="{{ $region }}" @selected(old('region', $user->region) === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Ciudad / distrito
                    <input type="text" name="city" placeholder="Ej. San Juan de Lurigancho"
                        value="{{ old('city', $user->city) }}" />
                </label>
                <label>
                    Universidad / instituto
                    <input type="text" name="university" list="profile-universidades"
                        placeholder="Elige o escribe tu casa de estudios"
                        value="{{ old('university', $user->university) }}" />
                    <datalist id="profile-universidades">
                        @foreach ($universidades as $universidad)
                            <option value="{{ $universidad }}"></option>
                        @endforeach
                    </datalist>
                </label>
                <label>
                    Carrera profesional
                    <input type="text" name="career" placeholder="Ej. Ingeniería Ambiental"
                        value="{{ old('career', $user->career) }}" />
                </label>
                <label>
                    Ciclo / grado
                    <select name="cycle">
                        <option value="">Selecciona</option>
                        @foreach ($ciclos as $ciclo)
                            <option value="{{ $ciclo }}" @selected(old('cycle', $user->cycle) === $ciclo)>{{ $ciclo }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Disponibilidad
                    <select name="availability">
                        <option value="">Horas por semana</option>
                        @foreach ($disponibilidades as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('availability', $user->availability) === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <p class="profile-modal-section">Causas que te mueven <small>(hasta 6)</small></p>
            <div class="profile-modal-interests">
                @foreach ($intereses as $interes)
                    <label class="check-row">
                        <input type="checkbox" name="interests[]" value="{{ $interes }}"
                            @checked(in_array($interes, $seleccionados, true)) />
                        <span>{{ $interes }}</span>
                    </label>
                @endforeach
            </div>

            <div class="profile-modal-actions">
                <a class="ghost-button" href="{{ route('voluntario.skills') }}" data-profile-skills>
                    Ajustar mis fortalezas <x-icon name="arrow" :size="13" />
                </a>
                <button type="submit" class="jelly-button">
                    Guardar mi perfil <x-icon name="check" />
                </button>
            </div>
        </form>
    </article>
</div>
