@php
    $porRevisar = $postulaciones->where('estado', 'en revision')->count();
@endphp
<a href="{{ route('ong.resumen') }}"
    class="{{ request()->routeIs('ong.resumen', 'dashboard-organizacion') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Resumen</span>
</a>
<a href="{{ route('ong.perfil') }}"
    class="{{ request()->routeIs('ong.perfil') ? 'active' : '' }}">
    <x-icon name="pin" /> <span>Mi ONG</span>
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
@php($avisosNuevos = auth()->user()->unreadNotifications()->count())
<a href="{{ route('notificaciones') }}"
    class="{{ request()->routeIs('notificaciones') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Notificaciones</span>
    @if ($avisosNuevos > 0)
        <i>{{ $avisosNuevos }}</i>
    @endif
</a>
