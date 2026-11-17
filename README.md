# VoluntMatch

Plataforma web que conecta estudiantes (voluntarios) con organizaciones sociales que necesitan talento para sus proyectos. La idea central: una persona quiere aportar su vocacion y una ONG busca esa vocacion para su causa. VoluntMatch hace el match entre ambos y acompana la relacion hasta el cierre del proyecto.

El proyecto esta construido sobre Laravel (aplicacion monolitica con Blade como capa de frontend). No es una SPA separada: cada pagina es una vista Blade servida por rutas web, y la interactividad (navegacion por pestanas de los paneles, sliders, chats, toasts, tema oscuro) vive en un unico archivo JavaScript vanilla.

![Pagina principal](assets/vmatch.png)


## Stack

- Laravel (PHP 8.x) con Blade templates.
- MySQL como base de datos (configurar en `.env`).
- CSS y JavaScript planos servidos directamente desde `public/css/app.css` y `public/js/app.js`.
  No hay Vite, Tailwind ni `npm`: el proyecto corre solo con PHP y Laravel.
- Autenticacion con el sistema de sesiones nativo de Laravel.

## Estructura del proyecto

```
voluntmatch/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php        # registro, login, logout y despacho de dashboards por rol
│   │   ├── DashboardController.php   # paneles de organizacion y administrador
│   │   └── OportunidadController.php # listado y fichas de oportunidades (datos demo)
│   ├── Models/
│   │   ├── User.php                  # usuario con columna role: voluntario | organizacion | admin
│   │   ├── Organizacion.php
│   │   ├── Oportunidad.php
│   │   └── Postulacion.php
│   └── View/Components/Icon.php      # componente <x-icon name="..." /> (iconos SVG inline)
├── database/migrations/              # users (+ role), cache, jobs, organizacions, oportunidads, postulacions
├── resources/
- `public/css/app.css`                      # todo el estilo del sitio y de los dashboards (CSS plano)
- `public/js/app.js`                        # interactividad: modales, secciones de dashboard, matches, chat, toasts, tema
│   └── views/
│       ├── layouts/base.blade.php    # plantilla base (head, tema claro/oscuro, scripts)
│       ├── partials/                 # header.blade.php y footer.blade.php reutilizables
│       ├── components/icon.blade.php # markup del componente de iconos
│       ├── index.blade.php           # landing publica
│       ├── oportunidades.blade.php   # listado publico de oportunidades
│       ├── detalle-oportunidad.blade.php # ficha de una oportunidad
│       ├── contacto.blade.php        # formulario de contacto publico
│       ├── modules/auth/             # login, registro-voluntario, registro-organizacion
│       └── dashboard/                # paneles privados: voluntario, organizacion, admin
├── routes/web.php                    # todas las rutas de la aplicacion
└── composer.json
```

## Roles y como fluye la sesion

La tabla `users` tiene una columna `role` con tres valores:

- `voluntario` (estudiante: se asigna al registrarse desde `/registro-voluntario`)
- `organizacion` (ONG: se asigna al registrarse desde `/registro-organizacion`)
- `admin` (cuenta creada por la migracion `2026_11_15_000001`, no se registra desde la web)

Tras iniciar sesion, la ruta `/home` despacha segun el rol: cada rol cae en su propio panel privado.

La cuenta administradora **no** existe de fabrica y sus credenciales nunca viven en el codigo: se crea con el seeder `AdminSeeder` leyendo `ADMIN_EMAIL` y `ADMIN_PASSWORD` desde tu `.env` (ver "Como correr el proyecto"). No uses la contrasenia de demo en ningun despliegue real ni la reutilices en otro sitio.

### Datos de registro (Peru)

Ambos formularios envian un campo oculto `role` que el controlador valida. Los catalogos de opciones viven en `app/Support/Peru.php` (regiones, ciclos, disponibilidad, causas, tipos de organizacion, sectores y universidades).

- Estudiante: nombre completo, DNI (8 digitos, unico), celular peruano (9 digitos que empieza con 9), correo, region, ciudad/distrito, universidad o instituto, carrera, ciclo/grado, disponibilidad semanal y causas de interes (hasta 6).
- ONG: nombre de la organizacion, RUC (11 digitos, empieza con 10/15/17/20, unico), tipo de organizacion, representante legal, celular, correo institucional, region, ciudad/distrito, direccion, sitio web opcional, causa/sector principal y mision.

Los mensajes de validacion estan en espanol en `lang/es/validation.php` (`APP_LOCALE=es`).

## Paginas publicas (sin sesion)

- `/` (landing, `index.blade.php`): presenta la propuesta de valor, las estadisticas, el proceso en tres pasos, causas destacadas, testimonios y FAQ.
- `/oportunidades` (`oportunidades.blade.php`): listado filtrable de oportunidades de voluntariado.
- `/detalle-oportunidad/{id}` (`detalle-oportunidad.blade.php`): ficha completa de una oportunidad con postulacion.
- `/contacto` (`contacto.blade.php`): formulario de contacto con validacion.
- `/login`, `/registro-voluntario`, `/registro-organizacion` (`modules/auth/*`): acceso y altas. Los dos formularios de registro comparten la ruta `POST /registrar`; cada uno envia su `role` oculto (`voluntario` u `organizacion`) y el controlador valida los campos propios de cada tipo de cuenta.

## Panel del voluntario

Ruta: `/home` (rol `voluntario`), vista `dashboard/voluntario.blade.php`, estilo `dashboard-student`.

Pestanas (navegacion por `data-section` / `data-panel` en `public/js/app.js`):

1. Mi resumen: metricas del voluntario, matches recomendados y completitud de perfil.
2. Skills Passport: mapa de fortalezas (grafico radar) y deslizadores para ajustar habilidades.
3. Descubrir matches: tarjetas tipo swiper de oportunidades con porcentaje de encaje; botones de descarte y postulacion.
4. Postulaciones: tabla de seguimiento con estados (en revision, aceptado, en curso).
5. Mi impacto: horas acumuladas, insignias, constancias digitales y valoracion de experiencias.
6. Mensajes: chat con organizaciones (lista de conversaciones, hilos y contexto del proyecto compartido).

## Panel de la organizacion

Ruta: `/dashboard-organizacion` (rol `organizacion`), vista `dashboard/organizacion.blade.php`.

Pestanas:

1. Resumen: metricas de la ONG, actividad de proyectos y atajo para buscar talento.
2. Oportunidades: gestion de proyectos publicados (crear, editar estado).
3. Buscar talento: busqueda de voluntarios por habilidad.
4. Candidatos (Match Inbox): bandeja de postulaciones recibidas en vista lista o kanban, con etapas (por revisar, en entrevista, aceptado, rechazado), rechazo con mensaje automatico, proyecto al que postula cada candidato y notas internas del equipo.
5. Metricas: impacto agregado con selector de rango de fechas (ultimo mes, trimestre, ano, historico), grafico de evolucion de horas, exportacion del reporte a CSV/PDF y evaluacion de voluntarios con proyecto asociado.
6. Mensajes: chat directo con voluntarios postulados, plantillas de respuesta rapida, agendado de reunion (Google Meet o invitacion de calendario), gestor de archivos compartidos y ficha del proyecto compartido.

## Panel del administrador

Ruta: `/dashboard-admin` (rol `admin`), vista `dashboard/admin.blade.php`. El controlador rechaza con error 403 a cualquier usuario cuyo rol no sea `admin`.

Pestanas:

1. Dashboard maestro: metricas globales del ecosistema (estudiantes activos, ONG verificadas, matches, horas), grafico de actividad semanal y alertas de tareas pendientes.
2. Verificar ONG: revision de organizaciones pendientes con documentos fiscales; acciones de verificar o rechazar.
3. Usuarios y reportes: moderacion de cuentas (revisar historial, bloquear, reactivar) y exportacion del registro.
4. Analiticas: demanda de habilidades y distribucion por universidades y carreras.
5. Algoritmo: pesos del motor de matching (afinidad de habilidades, horario, causa, geografia) ajustables con deslizadores.
6. Soporte: cola de casos de soporte tecnico con prioridad.

## Como correr el proyecto

Requisitos: PHP >= 8.2 con las extensiones usuales de Laravel (pdo_sqlite o pdo_mysql) y Composer. No se necesita Node/npm.

```bash
composer install
cp .env.example .env      # configurar la base de datos en .env (ver abajo)
php artisan key:generate
php artisan migrate       # crea las tablas
php artisan db:seed       # crea la cuenta admin leyendo ADMIN_EMAIL y ADMIN_PASSWORD del .env
php artisan serve
```

Base de datos:

- Por defecto `.env.example` usa `DB_CONNECTION=sqlite` y funciona sin configurar nada mas.
- Para MySQL: crea una base vacia (`CREATE DATABASE voluntmatch;`) y define en `.env` las variables `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. `APP_LOCALE=es` ya viene configurado.
- Define en `.env` las credenciales del admin antes del `db:seed`: `ADMIN_EMAIL` y `ADMIN_PASSWORD` (una contrasenia larga y unica; si la dejas vacia, el seeder genera una aleatoria y la muestra por consola una sola vez).

## Notas de implementacion para quien mantenga el codigo

- La navegacion entre pestanas de los tres dashboards no es rutas separadas: son divs con `data-panel` dentro de la misma vista, y `public/js/app.js` muestra/oculta segun el boton con `data-section`. El encabezado del panel (`data-dash-heading`) y la etiqueta superior se actualizan con JavaScript.
- En el panel de la ONG, las fichas de proyecto se abren en un modal (`data-project-open` + `data-project-modal` en `public/js/app.js`), con acciones de ONG: editar proyecto y ver postulantes. El enlace "Ver ficha publica" lleva a `/detalle-oportunidad/{id}`.
- Los avisos flotantes (toasts) se disparan con el atributo `data-toast-msg="texto"` en cualquier boton.
- El Match Inbox del panel de la ONG (pestanas Candidatos) guarda las etapas y las notas internas de cada candidato en `localStorage` bajo la clave `voluntmatch-inbox`: los cambios sobreviven a recargas de pagina hasta que se borren los datos del navegador.
- El tema oscuro se guarda en `localStorage` bajo la clave `voluntmatch-theme` (coordinado entre `layouts/base.blade.php` y `public/js/app.js`).
- Las oportunidades que se muestran en la landing, los dashboards y las fichas provienen de `OportunidadController::demo()` (datos de ejemplo en memoria); la tabla `oportunidads` existe pero el contenido demo aun no esta migrado a ella.
- Las marcas "nexo" antiguas fueron reemplazadas por "VoluntMatch" en todas las vistas, el footer y el JavaScript.
