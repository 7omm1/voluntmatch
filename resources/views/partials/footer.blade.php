<footer>
    <div class="footer-top">
        <div class="footer-brand">
            <a class="brand" href="{{ route('index') }}">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </a>
            <p>
                Talento joven.<br />
                Impacto que transforma.
            </p>
        </div>
        <div class="footer-column">
            <b>EXPLORA</b>
            <a href="{{ route('oportunidades', ['tab' => 'participate']) }}">Cómo funciona</a>
            <a href="{{ route('oportunidades') }}">Proyectos</a>
            <a href="{{ route('index') }}#comunidad">Comunidad</a>
            <a href="{{ route('contacto') }}">Contacto</a>
        </div>
        <div class="footer-column">
            <b>PARA TI</b>
            <a href="{{ route('registro-voluntario') }}">Soy estudiante</a>
            <a href="{{ route('registro-organizacion') }}">Soy una ONG</a>
            <a href="{{ route('index') }}#nosotros">Sobre VoluntMatch</a>
        </div>
        <div class="footer-designers">
            <b>DISEÑADO CON PROPÓSITO POR</b>
            <div>
                <span>Lucía Ortega</span>
                <span>Martín Ferrer</span>
                <span>Alba Núñez</span>
                <span>Diego Ríos</span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2025 VoluntMatch. Hecho para conectar.</span>
        <div>
            <a href="#">Privacidad</a>
            <a href="#">Términos</a>
            <a href="#">Cookies</a>
        </div>
        <span class="footer-status">
            <i></i> Todo funciona con propósito
        </span>
    </div>
</footer>
