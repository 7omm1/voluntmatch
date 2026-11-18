@extends('layouts.panel')

@section('title', 'Mi ONG — Panel ONG')
@section('panel-section', 'MI ONG')
@section('panel-heading', 'Tu ficha pública.')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('panel-actions')
    <a class="dash-create-button" href="{{ $organizacion->urlPublica() ?: route('ongs.index') }}" target="_blank"
        rel="noopener">
        Ver ficha pública
    </a>
@endsection

@section('panel-content')
    <div class="ong-profile-editor">
        @if (! $organizacion->verificada)
            <section class="dashboard-card ong-verify-notice">
                <span class="list-icon"><x-icon name="clock" /></span>
                <div>
                    <h3>Estamos revisando tu organización.</h3>
                    <p>
                        Puedes completar tu ficha desde ahora. Cuando el equipo de VoluntMatch apruebe la
                        verificación, aparecerá en el directorio y en el mapa de Cusco.
                    </p>
                </div>
            </section>
        @endif

        <form method="POST" action="{{ route('ong.perfil.update') }}" class="ong-profile-form">
            @csrf
            @method('PATCH')

            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>IDENTIDAD PÚBLICA</p>
                        <h3>Así te verán las y los estudiantes</h3>
                    </div>
                    <span>{{ $organizacion->verificada ? 'Verificada' : 'En revisión' }}</span>
                </div>

                <div class="project-create-form">
                    <label>
                        <span>Nombre público</span>
                        <input type="text" name="nombre" value="{{ old('nombre', $organizacion->nombre) }}"
                            required maxlength="255" />
                    </label>
                    <label>
                        <span>Causa o sector principal</span>
                        <select name="sector" required>
                            @foreach ($sectores as $sector)
                                <option value="{{ $sector }}"
                                    @selected(old('sector', $organizacion->sector) === $sector)>{{ $sector }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Ciudad / distrito</span>
                        <input type="text" name="ciudad" value="{{ old('ciudad', $organizacion->ciudad) }}"
                            maxlength="100" placeholder="Ej. Cusco · San Blas" />
                    </label>
                    <label>
                        <span>Año de fundación</span>
                        <input type="number" name="anio_fundacion"
                            value="{{ old('anio_fundacion', $organizacion->anio_fundacion) }}" min="1900"
                            max="{{ now()->year }}" placeholder="Ej. 2014" />
                    </label>
                    <label>
                        <span>Teléfono de contacto</span>
                        <input type="text" name="telefono" value="{{ old('telefono', $organizacion->telefono) }}"
                            maxlength="30" placeholder="+51 984 000 000" />
                    </label>
                    <label>
                        <span>Sitio web</span>
                        <input type="url" name="sitio_web" value="{{ old('sitio_web', $organizacion->sitio_web) }}"
                            maxlength="255" placeholder="https://mi-ong.org" />
                    </label>
                    <label>
                        <span>Logo (URL)</span>
                        <input type="url" name="logo_url" value="{{ old('logo_url', $organizacion->logo_url) }}"
                            maxlength="255" placeholder="https://.../logo.png" />
                    </label>
                    <label>
                        <span>Portada (URL)</span>
                        <input type="url" name="portada_url"
                            value="{{ old('portada_url', $organizacion->portada_url) }}" maxlength="255"
                            placeholder="https://.../portada.jpg" />
                    </label>

                    <label class="project-create-wide">
                        <span>Misión en una frase</span>
                        <textarea name="mision" rows="2" maxlength="1000"
                            placeholder="Lo que cambia tu organización en una sola frase.">{{ old('mision', $organizacion->mision) }}</textarea>
                    </label>

                    <label class="project-create-wide">
                        <span>Quiénes son y qué hacen</span>
                        <textarea name="descripcion" rows="4" maxlength="3000"
                            placeholder="Cuenta la historia de la organización, a quién acompaña y qué ha logrado.">{{ old('descripcion', $organizacion->descripcion) }}</textarea>
                    </label>

                    <label class="project-create-wide">
                        <span>Cómo se organizan y cómo se participa</span>
                        <textarea name="como_participar" rows="4" maxlength="2000"
                            placeholder="Explica horarios, equipos, compromisos y la forma de sumarse.">{{ old('como_participar', $organizacion->como_participar) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="dashboard-card">
                <div class="card-heading">
                    <div>
                        <p>UBICACIÓN EN EL MAPA</p>
                        <h3>¿Dónde trabaja tu ONG?</h3>
                    </div>
                </div>
                <p class="ong-location-help">
                    Haz clic en el mapa (o mueve el marcador) para fijar la ubicación que aparecerá en el
                    directorio público. Puedes dejarla vacía si tu organización trabaja solo de forma virtual.
                </p>

                <div class="ong-map-picker" data-ongs-picker data-lat-input="#ong-lat"
                    data-lng-input="#ong-lng" data-hint="#ong-location-hint">
                    <p class="map-aviso">Cargando el mapa…</p>
                </div>

                <div class="ong-coords">
                    <label>
                        <span>Latitud</span>
                        <input id="ong-lat" type="number" step="0.0000001" name="latitud"
                            value="{{ old('latitud', $organizacion->latitud) }}" placeholder="-13.5319500" />
                    </label>
                    <label>
                        <span>Longitud</span>
                        <input id="ong-lng" type="number" step="0.0000001" name="longitud"
                            value="{{ old('longitud', $organizacion->longitud) }}" placeholder="-71.9674600" />
                    </label>
                    <label class="ong-address">
                        <span>Dirección</span>
                        <input type="text" name="direccion" value="{{ old('direccion', $organizacion->direccion) }}"
                            maxlength="255" placeholder="Calle, número y distrito" />
                    </label>
                </div>
                <p class="ong-location-hint" id="ong-location-hint">
                    @if ($organizacion->tieneUbicacion())
                        Ubicación actual: {{ number_format($organizacion->latitud, 5) }},
                        {{ number_format($organizacion->longitud, 5) }}
                    @else
                        Aún no has fijado una ubicación.
                    @endif
                </p>
            </section>

            <div class="ong-profile-form-actions">
                <button type="submit" class="jelly-button">
                    Guardar ficha pública <x-icon name="check" />
                </button>
                <a class="ghost-button" href="{{ route('ong.resumen') }}">Volver al resumen</a>
            </div>
        </form>
    </div>
@endsection
