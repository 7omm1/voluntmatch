@extends('layouts.admin')

@section('title', 'Configuración — VoluntMatch')
@section('panel-seccion', 'CONFIGURACIÓN')
@section('panel-titulo', 'Reglas de la plataforma.')

@section('admin-content')
    <form method="POST" action="{{ route('admin.configuracion.update') }}">
        @csrf
        @method('patch')

        <section class="dashboard-card algorithm-card">
            <div class="card-heading">
                <div>
                    <p>RECOMENDACIÓN</p>
                    <h3>Pesos del algoritmo de matching</h3>
                </div>
                <span>{{ $actualizado ? 'Actualizado '.$actualizado : 'Valores por defecto' }}</span>
            </div>

            @foreach ([
                'habilidades' => 'Afinidad de habilidades',
                'horario' => 'Compatibilidad horaria',
                'causa' => 'Interés por la causa',
                'cercania' => 'Proximidad geográfica',
            ] as $clave => $etiqueta)
                <label>
                    <span>
                        <b>{{ $etiqueta }}</b>
                        <em data-range-value>{{ $pesos[$clave] ?? 50 }}%</em>
                    </span>
                    <input type="range" min="0" max="100" name="{{ $clave }}"
                        value="{{ $pesos[$clave] ?? 50 }}" data-range>
                    <small>Peso de este factor al recomendar oportunidades a cada voluntario.</small>
                </label>
            @endforeach

            <div class="admin-form-grid">
                <label class="span-two">Aviso para la comunidad
                    <textarea name="aviso" rows="4" maxlength="500"
                        placeholder="Mensaje corto que puede mostrarse en los paneles">{{ $reglas['aviso'] ?? '' }}</textarea>
                </label>
                <label>Máximo de postulaciones por voluntario
                    <input type="number" name="max_postulaciones" min="1" max="50" required
                        value="{{ $reglas['max_postulaciones'] ?? 5 }}">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="ocultar_reportadas" value="1"
                        @checked($reglas['ocultar_reportadas'] ?? false)>
                    Ocultar automáticamente oportunidades con reportes pendientes
                </label>
            </div>

            <button type="submit" class="jelly-button">
                Guardar configuración <x-icon name="check" />
            </button>
        </section>
    </form>

    <section class="dashboard-card">
        <div class="card-heading">
            <div><p>REFERENCIA</p><h3>Cómo se aplican estos valores</h3></div>
        </div>
        <ul class="admin-help-list">
            <li>Los pesos se guardan de forma persistente y quedan disponibles para el motor de recomendaciones.</li>
            <li>El límite de postulaciones protege a los voluntarios de dispersarse en demasiados retos.</li>
            <li>Cada cambio queda registrado en la <a href="{{ route('admin.actividad') }}">bitácora de auditoría</a>.</li>
        </ul>
    </section>
@endsection
