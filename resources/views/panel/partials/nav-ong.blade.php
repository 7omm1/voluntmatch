@php
    $porRevisar = $postulaciones->where('estado', 'en revision')->count();
@endphp
<a href="{{ route('ong.resumen') }}"
    class="{{ request()->routeIs('ong.resumen', 'dashboard-organizacion') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Resumen</span>
</a>
<a href="{{ route('ong.oportunidades') }}"
    class="{{ request()->routeIs('ong.oportunidades') ? 'active' : '' }}">
    <x-icon name="heart" /> <span>Oportunidades</span>
</a>
<a href="{{ route('ong.talento') }}"
    class="{{ request()->routeIs('ong.talento') ? 'active' : '' }}">
    <x-icon name="users" /> <span>Buscar talento</span>
</a>
<a href="{{ route('ong.candidatos') }}"
    class="{{ request()->routeIs('ong.candidatos') ? 'active' : '' }}">
    <x-icon name="check" /> <span>Candidatos</span>
    @if ($porRevisar > 0)
        <i>{{ $porRevisar }}</i>
    @endif
</a>
<a href="{{ route('ong.metricas') }}"
    class="{{ request()->routeIs('ong.metricas') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Métricas</span>
</a>
<a href="{{ route('ong.mensajes') }}"
    class="{{ request()->routeIs('ong.mensajes') ? 'active' : '' }}">
    <x-icon name="users" /> <span>Mensajes</span>
    @if ($noLeidos > 0)
        <i>{{ $noLeidos }}</i>
    @endif
</a>
