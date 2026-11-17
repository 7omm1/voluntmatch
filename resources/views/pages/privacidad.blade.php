@extends('layouts.base')

@section('title', 'Política de privacidad — VoluntMatch')

@section('content')
    <div class="page-panel">
        <div class="panel-ambient" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="panel-topbar">
            <a class="brand" href="{{ route('index') }}">
                <span class="brand-mark">
                    <span></span>
                    <span></span>
                </span>
                <span>VOLUNTMATCH</span>
            </a>
            <a class="panel-close" href="{{ route('index') }}">
                <x-icon name="close" /> Cerrar
            </a>
        </div>

        <div class="policy-page">
            <p class="eyebrow dark-text">TRANSPARENCIA Y DATOS</p>
            <h1>Política de privacidad</h1>
            <p class="policy-updated">Última actualización: noviembre de 2026</p>

            <p>
                VoluntMatch trata los datos personales de estudiantes y organizaciones conforme a la
                <strong>Ley N.º 29733 de Protección de Datos Personales del Perú</strong> y su reglamento.
                Este documento explica qué datos recogemos, para qué los usamos y qué derechos tienes.
            </p>

            <h2>1. Qué datos recogemos</h2>
            <ul>
                <li><strong>Estudiantes:</strong> nombre, DNI, celular, correo, región, ciudad/distrito,
                    universidad o instituto, carrera, ciclo, disponibilidad semanal y causas de interés.</li>
                <li><strong>Organizaciones:</strong> nombre de la organización, RUC, tipo, representante legal,
                    celular, correo institucional, región, ciudad/distrito, dirección, sitio web y misión.</li>
                <li><strong>Uso de la plataforma:</strong> postulaciones a oportunidades, mensajes y valoraciones
                    que se generan al usar el panel.</li>
            </ul>

            <h2>2. Para qué los usamos</h2>
            <ul>
                <li>Crear y gestionar tu cuenta, y calcular el porcentaje de afinidad entre perfiles y proyectos.</li>
                <li>Permitir que las organizaciones revisen postulaciones y comuniquen contigo.</li>
                <li>Verificar la identidad de las ONG registradas (RUC y representante legal).</li>
                <li>Generar métricas agregadas de impacto. Los reportes exportables solo los puede
                    generar la administración de la organización o del ecosistema, nunca otros usuarios.</li>
            </ul>

            <h2>3. Consentimiento</h2>
            <p>
                Al marcar la casilla de aceptación en el registro autorizas de forma libre, previa, expresa e
                informada el tratamiento de tus datos para las finalidades descritas. Puedes retirar el
                consentimiento escribiéndonos; retirarlo implica la eliminación de tu cuenta y de los datos
                asociados, salvo obligaciones legales.
            </p>

            <h2>4. Quién puede ver tus datos</h2>
            <ul>
                <li>Tu perfil resumido (nombre, carrera, habilidades y disponibilidad) es visible para las
                    organizaciones cuando postulas o haces match.</li>
                <li>Tu DNI y celular no se publican; solo se usan para verificación y contacto directo.</li>
                <li>La exportación de registros con datos personales está limitada a la cuenta de
                    administración del ecosistema.</li>
            </ul>

            <h2>5. Tus derechos (ARCO)</h2>
            <p>
                Puedes ejercer tus derechos de <strong>acceso, rectificación, cancelación y oposición</strong>
                escribiendo a <a href="mailto:privacidad@voluntmatch.org">privacidad@voluntmatch.org</a>.
                Responderemos en un plazo razonable según la normativa vigente.
            </p>

            <h2>6. Seguridad y conservación</h2>
            <p>
                Las contraseñas se almacenan cifradas (bcrypt), la sesión se protege contra reutilización y el
                acceso a los paneles privados está limitado por rol. Conservamos tus datos mientras tu cuenta
                esté activa; después se eliminan o anonimizan.
            </p>

            <p class="policy-note">
                VoluntMatch es un proyecto de demostración. Si se despliega en producción, esta política debe
                revisarse junto al responsable legal de la organización y registrarla ante la Autoridad Nacional
                de Protección de Datos Personales si corresponde.
            </p>
        </div>
    </div>
@endsection
