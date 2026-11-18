@php
    $revision = $postulaciones->where('estado', 'en revision')->count();
@endphp
<a href="{{ route('voluntario.resumen') }}"
    class="{{ request()->routeIs('voluntario.resumen', 'home') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Mi resumen</span>
</a>
<a href="{{ route('voluntario.skills') }}"
    class="{{ request()->routeIs('voluntario.skills') ? 'active' : '' }}">
    <x-icon name="users" /> <span>Skills Passport</span>
</a>
<a href="{{ route('voluntario.matches') }}"
    class="{{ request()->routeIs('voluntario.matches') ? 'active' : '' }}">
    <x-icon name="heart" /> <span>Descubrir matches</span>
</a>
<a href="{{ route('voluntario.postulaciones') }}"
    class="{{ request()->routeIs('voluntario.postulaciones') ? 'active' : '' }}">
    <x-icon name="check" /> <span>Postulaciones</span>
    @if ($revision > 0)
        <i>{{ $revision }}</i>
    @endif
</a>
<a href="{{ route('voluntario.impacto') }}"
    class="{{ request()->routeIs('voluntario.impacto') ? 'active' : '' }}">
    <x-icon name="spark" /> <span>Mi impacto</span>
</a>
<a href="{{ route('voluntario.mensajes') }}"
    class="{{ request()->routeIs('voluntario.mensajes') ? 'active' : '' }}">
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
