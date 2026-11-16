@extends('layouts.base')

@section('content')
    <div class="cursor-glow" data-cursor-glow></div>
    <div class="ambient-dots" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    @include('partials.header')

    <main>
        @yield('main')
    </main>

    @include('partials.footer')
@endsection
