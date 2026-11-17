@php
    $autoAbrir = old('_modal') === 'ong';
@endphp

{{--
    Ventana de registro de ONG. Se inyecta en el layout base para que el
    formulario se abra sobre cualquier página sin tener que desplazarse
    hasta una sección concreta. Los enlaces sin JavaScript siguen
    apuntando a /registro-organizacion.
--}}
<div class="modal-backdrop ngo-modal-backdrop" data-ngo-modal data-autoopen="{{ $autoAbrir ? '1' : '0' }}"
    hidden>
    <div class="auth-modal wide ngo-modal" role="dialog" aria-modal="true" aria-labelledby="ngo-modal-title"
        aria-describedby="ngo-modal-desc">
        <button class="modal-close" type="button" data-ngo-modal-close aria-label="Cerrar registro de ONG">
            <x-icon name="close" />
        </button>

        <div class="auth-heading">
            <span class="mini-mark"><x-icon name="heart" :size="18" /></span>
            <p class="eyebrow dark-text">VOLUNTMATCH PARA ORGANIZACIONES</p>
            <h2 id="ngo-modal-title">Registra tu ONG en 2 minutos.</h2>
            <p class="ngo-modal-sub" id="ngo-modal-desc">
                Solo lo esencial. Añade más datos únicamente si lo necesitas.
            </p>
        </div>

        @include('partials.formulario-ong', ['modal' => true])

        <a class="auth-back" href="{{ route('login') }}">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
</div>
