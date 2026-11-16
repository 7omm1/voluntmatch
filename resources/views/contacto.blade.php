@extends('layouts.app')

@section('title', 'Contacto — VoluntMatch')

@section('main')
    <div class="contact-layout">
        <div class="contact-info">
            <p class="eyebrow dark-text"><span></span> HABLEMOS</p>
            <h2>Estamos aquí para <em>ayudarte.</em></h2>
            <p class="lead">
                ¿Tienes dudas sobre cómo participar, quieres registrar tu organización o simplemente
                quieres saludar? Escríbenos y te respondemos muy pronto.
            </p>
            <div class="contact-cards">
                <div class="contact-card">
                    <b>Correo</b>
                    <small>hola@voluntmatch.org</small>
                </div>
                <div class="contact-card">
                    <b>Horario de atención</b>
                    <small>Lunes a viernes · 9:00 – 18:00</small>
                </div>
                <div class="contact-card">
                    <b>Soporte para ONG</b>
                    <small>ong@voluntmatch.org</small>
                </div>
            </div>
        </div>

        <form class="contact-form" method="POST" action="{{ route('contacto.enviar') }}">
            @csrf
            @if (session('success'))
                <div class="form-success" role="status">
                    <x-icon name="check" :size="16" /> {{ session('success') }}
                </div>
            @endif
            <p class="eyebrow light-text"><span></span> ESCRÍBENOS</p>
            <h2>Tu mensaje importa.</h2>
            <label>
                Nombre
                <input type="text" name="name" required placeholder="Tu nombre" value="{{ old('name') }}" />
            </label>
            <label>
                Correo electrónico
                <input type="email" name="email" required placeholder="tu@email.com" value="{{ old('email') }}" />
            </label>
            <label>
                Mensaje
                <textarea name="message" rows="5" required placeholder="Cuéntanos en qué podemos ayudarte...">{{ old('message') }}</textarea>
            </label>
            <button class="jelly-button" type="submit">
                Enviar mensaje <x-icon name="arrow" />
            </button>
            <small class="form-note">Respondemos en un máximo de 48 horas laborables.</small>
        </form>
    </div>
@endsection
