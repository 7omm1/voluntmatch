<?php

/*
|--------------------------------------------------------------------------
| Integraciones reales
|--------------------------------------------------------------------------
|
| El asistente puede enviar correo, crear eventos de calendario, registrar
| contactos en un CRM, abrir casos en la mesa de ayuda y emitir
| notificaciones. Cada canal tiene un driver local (funciona sin
| credenciales) y un driver HTTP genérico para conectar el proveedor real
| (Google Calendar, HubSpot, Zendesk, Slack, etc.).
|
| Nunca se aceptan URLs ni tokens enviados por el usuario o por el modelo:
| todo sale de esta configuración.
|
*/

return [

    'mail' => [
        // laravel | disabled. Usa MAIL_* de Laravel (SMTP, Resend, Postmark…).
        'driver' => env('INTEGRATION_MAIL_DRIVER', 'laravel'),
        'from_address' => env('INTEGRATION_MAIL_FROM', env('MAIL_FROM_ADDRESS', 'no-reply@voluntmatch.org')),
        'from_name' => env('INTEGRATION_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'VoluntMatch')),
        'rate' => [
            'per_hour' => (int) env('INTEGRATION_MAIL_PER_HOUR', 20),
            'per_day' => (int) env('INTEGRATION_MAIL_PER_DAY', 60),
        ],
        // Dominios a los que la administración puede escribir sin que el
        // destinatario tenga cuenta en la plataforma.
        'allowed_domains' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('INTEGRATION_MAIL_ALLOWED_DOMAINS', 'voluntmatch.org,voluntmatch.test'))
        ))),
    ],

    'calendar' => [
        // ics | http | disabled. 'ics' genera una invitación .ics firmada y
        // no necesita credenciales; 'http' publica el evento en tu proveedor.
        'driver' => env('INTEGRATION_CALENDAR_DRIVER', 'ics'),
        'timezone' => env('INTEGRATION_CALENDAR_TIMEZONE', 'America/Lima'),
        'url' => env('INTEGRATION_CALENDAR_URL'),
        'token' => env('INTEGRATION_CALENDAR_TOKEN'),
        'timeout' => (int) env('INTEGRATION_CALENDAR_TIMEOUT', 8),
        'link_days' => (int) env('INTEGRATION_CALENDAR_LINK_DAYS', 7),
    ],

    'crm' => [
        // local | http | disabled. 'local' guarda los contactos en la tabla
        // crm_contacts y los muestra en el panel de administración.
        'driver' => env('INTEGRATION_CRM_DRIVER', 'local'),
        'url' => env('INTEGRATION_CRM_URL'),
        'token' => env('INTEGRATION_CRM_TOKEN'),
        'timeout' => (int) env('INTEGRATION_CRM_TIMEOUT', 8),
        'stages' => ['nuevo', 'contactado', 'entrevista', 'voluntario', 'descartado'],
    ],

    'helpdesk' => [
        // local | http | disabled. 'local' abre un ticket en la mesa de
        // ayuda interna; 'http' además lo replica en Zendesk/Freshdesk.
        'driver' => env('INTEGRATION_HELPDESK_DRIVER', 'local'),
        'url' => env('INTEGRATION_HELPDESK_URL'),
        'token' => env('INTEGRATION_HELPDESK_TOKEN'),
        'timeout' => (int) env('INTEGRATION_HELPDESK_TIMEOUT', 8),
        'queue' => env('INTEGRATION_HELPDESK_QUEUE', 'soporte-voluntmatch'),
    ],

    'notifications' => [
        // database | webhook | both | disabled.
        'driver' => env('INTEGRATION_NOTIFICATIONS_DRIVER', 'database'),
        'webhook_url' => env('INTEGRATION_NOTIFICATIONS_WEBHOOK_URL'),
        'timeout' => (int) env('INTEGRATION_NOTIFICATIONS_TIMEOUT', 6),
    ],

    'deliveries' => [
        'store' => (bool) env('INTEGRATION_STORE_DELIVERIES', true),
        'keep_days' => (int) env('INTEGRATION_KEEP_DAYS', 90),
    ],

];
