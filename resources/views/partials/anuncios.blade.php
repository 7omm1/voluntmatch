@if (isset($anuncios) && $anuncios->isNotEmpty())
    <div class="dashboard-announcements">
        @foreach ($anuncios as $anuncio)
            <article>
                <span><x-icon name="spark" :size="16" /></span>
                <div>
                    <b>{{ $anuncio->titulo }}</b>
                    <p>{{ $anuncio->cuerpo }}</p>
                </div>
                <small>{{ $anuncio->created_at?->diffForHumans() }}</small>
            </article>
        @endforeach
    </div>
@endif
