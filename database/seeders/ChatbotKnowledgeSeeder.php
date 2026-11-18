<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledgeDocument;
use App\Services\Chatbot\Rag\KnowledgeIndexer;
use Illuminate\Database\Seeder;

/**
 * Corpus inicial del asistente.
 *
 * Cada documento declara sus audiencias. El retriever filtra por el rol
 * real de la sesión, así que un visitante nunca recupera guías internas de
 * administración. El contenido se puede editar desde la base de datos y
 * reindexar con `php artisan chatbot:reindex`.
 */
class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $documentos = [
            [
                'slug' => 'como-funciona-voluntmatch',
                'title' => 'Cómo funciona VoluntMatch',
                'audiences' => ['public'],
                'tags' => ['plataforma', 'voluntariado', 'ong', 'funcionamiento', 'inicio'],
                'source' => 'Guía general de VoluntMatch',
                'body' => <<<'TXT'
VoluntMatch es una plataforma peruana que conecta a estudiantes que quieren aportar su talento con organizaciones sociales que necesitan apoyo para sus proyectos.

## El recorrido en tres pasos
1. La persona crea una cuenta de estudiante o registra su organización.
2. Explora oportunidades publicadas, revisa la ficha del reto y se postula. La organización recibe la postulación en su panel.
3. La organización revisa el perfil, conversa con la persona y actualiza el estado de la postulación: en revisión, en entrevista, aceptada o rechazada.

## Qué ofrece cada lado
Los estudiantes encuentran retos por causa, habilidad, modalidad y dedicación; pueden guardar un perfil de habilidades, revisar sus matches y seguir sus postulaciones. Las organizaciones publican oportunidades, exploran talento, gestionan candidatos y consultan métricas de impacto.

## Reglas importantes
Una persona puede postular una sola vez a cada oportunidad. Todas las postulaciones empiezan en revisión. Ninguna herramienta ni persona externa puede cambiar el estado de una postulación: solo la organización dueña del reto o la administración de VoluntMatch.

Para explorar oportunidades entra en la sección Oportunidades. Para conocer organizaciones verificadas entra en el directorio de ONG.
TXT,
            ],
            [
                'slug' => 'registro-voluntario',
                'title' => 'Registro de estudiante voluntario',
                'audiences' => ['public'],
                'tags' => ['registro', 'voluntario', 'estudiante', 'cuenta', 'dni', 'celular'],
                'source' => 'Guía de registro de VoluntMatch',
                'body' => <<<'TXT'
Cualquier estudiante puede crear una cuenta gratuita desde la página Registro de voluntario.

## Datos que se solicitan
- Nombre completo y correo electrónico.
- DNI de 8 dígitos. Debe ser único en la plataforma.
- Celular peruano de 9 dígitos que empieza con 9.
- Región y ciudad o distrito.
- Universidad o instituto, carrera y ciclo o grado.
- Disponibilidad semanal y causas de interés (hasta 6).
- Una contraseña segura y la aceptación de la política de privacidad.

## Después del registro
La cuenta se crea con el rol de voluntario. Puedes iniciar sesión y entrar a Mi espacio para completar tu perfil, ajustar tus habilidades, ver matches y revisar tus postulaciones.

## Recomendaciones
Usa un correo al que tengas acceso: por ahí se enviarán avisos importantes. No compartas tu contraseña con nadie, ni siquiera con el equipo de soporte. Si olvidaste tu contraseña o no puedes entrar, escribe desde la página de contacto o abre un caso de soporte.
TXT,
            ],
            [
                'slug' => 'registro-ong-verificacion',
                'title' => 'Registro y verificación de organizaciones',
                'audiences' => ['public'],
                'tags' => ['registro', 'ong', 'organizacion', 'ruc', 'verificacion', 'sector'],
                'source' => 'Guía de organizaciones de VoluntMatch',
                'body' => <<<'TXT'
Las organizaciones sociales se registran desde la página Registrar ONG. El formulario también se abre en una ventana sobre cualquier página pública.

## Datos esenciales
- Nombre de la organización y RUC de 11 dígitos.
- Causa o sector, región y ciudad.
- Celular de contacto y correo institucional.
- Contraseña segura.
- De forma opcional: tipo de organización, representante legal, dirección, sitio web y misión.

## Validación del RUC
El RUC debe empezar con 10, 15, 17 o 20 y tener un dígito verificador válido según el algoritmo módulo 11 de SUNAT. No se aceptan RUC inventados ni duplicados. El sistema no publica el RUC en el directorio.

## Verificación
Toda organización queda pendiente de verificación hasta que la administración revise su expediente. Cuando se verifica, su ficha puede aparecer en el directorio público y sus oportunidades se muestran con el sello de organización verificada. Si la verificación es rechazada, la administración deja una nota con el motivo.

## Qué puede hacer una ONG
Publicar oportunidades, buscar talento por carrera y habilidades, gestionar candidatos en el Match Inbox, escribir notas internas, revisar métricas y actualizar el perfil de la organización.
TXT,
            ],
            [
                'slug' => 'postulaciones-y-estados',
                'title' => 'Cómo postular y qué significan los estados',
                'audiences' => ['public'],
                'tags' => ['postulacion', 'postular', 'estado', 'entrevista', 'aceptado', 'rechazado', 'revision'],
                'source' => 'Guía de postulaciones de VoluntMatch',
                'body' => <<<'TXT'
## Cómo postularse
1. Entra a Oportunidades y abre la ficha de un reto.
2. Revisa la causa, la habilidad buscada, la modalidad y la dedicación.
3. Si la oportunidad está publicada, usa el botón de postulación y escribe una nota breve si lo deseas.
4. La postulación aparece en tu panel Mi espacio, sección Postulaciones.

También puedes postularte desde la baraja de matches de tu panel. Cada reto acepta una sola postulación por persona; si vuelves a intentarlo, el sistema no crea un duplicado.

## Estados posibles
- En revisión: la organización todavía no revisó la postulación. Es el estado inicial.
- En entrevista: la organización quiere conversar contigo o ya agendó una entrevista.
- Aceptado: fuiste seleccionado para el reto. El proyecto pasa a la etapa de coordinación.
- Rechazado: la organización no continuará con la postulación en esta ocasión. Puedes postularte a otros retos.

## Seguimiento
En Postulaciones puedes abrir cada fila para ver la línea de tiempo, la nota que enviaste y el acceso para completar tu perfil. Solo la organización dueña del reto puede mover el estado; si detectas un error, contacta a soporte.
TXT,
            ],
            [
                'slug' => 'panel-voluntario',
                'title' => 'Guía del panel del estudiante',
                'audiences' => ['voluntario'],
                'tags' => ['panel', 'estudiante', 'skills', 'matches', 'postulaciones', 'impacto', 'perfil'],
                'source' => 'Guía interna del panel del voluntario',
                'body' => <<<'TXT'
El espacio del estudiante vive en la ruta Mi espacio. Tiene estas secciones:

- Resumen: métricas de tu actividad, matches recomendados y porcentaje de perfil completado.
- Skills Passport: mapa de fortalezas editable. Cada ajuste del deslizador actualiza el porcentaje del perfil y se guarda en tu cuenta.
- Matches: baraja de oportunidades con encaje explicado. Puedes abrir la ficha del reto o postularte.
- Postulaciones: seguimiento de cada postulación, estado, línea de tiempo y nota enviada.
- Impacto: horas estimadas de los proyectos aceptados, insignias y constancias.
- Mensajes: conversaciones con organizaciones.

## Completar el perfil
Un perfil completo mejora la calidad de los matches. Revisa tus datos personales, habilidades, disponibilidad y causas de interés. Puedes abrir la ventana Completar perfil desde tu panel sin salir de la sección.

## Privacidad
Tu DNI y celular no se publican. Las organizaciones ven un perfil resumido cuando postulas o haces match. Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición escribiendo a privacidad@voluntmatch.org.
TXT,
            ],
            [
                'slug' => 'panel-ong',
                'title' => 'Guía del panel de la organización',
                'audiences' => ['organizacion'],
                'tags' => ['panel', 'ong', 'oportunidades', 'candidatos', 'talento', 'metricas', 'verificacion'],
                'source' => 'Guía interna del panel de ONG',
                'body' => <<<'TXT'
El panel de la organización vive en la ruta Panel ONG. Sus secciones principales son:

- Resumen: métricas reales de la organización, tareas pendientes y próximos hitos.
- Oportunidades: publicación y gestión de retos. Puedes crear una oportunidad con título, causa, habilidad, dedicación y modalidad.
- Talento: búsqueda de estudiantes por carrera, habilidad y disponibilidad, con favoritos.
- Candidatos: Match Inbox conectado a las postulaciones. Permite aceptar, rechazar, mover etapas y guardar notas internas.
- Métricas: impacto agregado, horas, áreas, evaluación de voluntarios y exportación.
- Mensajes: conversaciones con voluntarios.
- Mi ONG: edición del perfil público y estado de verificación.

## Buenas prácticas
Revisa las postulaciones con frecuencia y actualiza el estado para que el estudiante sepa qué sigue. No compartas datos personales de candidatos fuera de la plataforma. Las notas internas solo las ve tu organización.

## Verificación
Si tu organización aún no está verificada, revisa que el RUC y los datos de contacto sean correctos. La administración puede dejar una nota con el motivo del rechazo. Mientras tanto, puedes completar tu perfil y preparar tus oportunidades.
TXT,
            ],
            [
                'slug' => 'cuenta-seguridad-privacidad',
                'title' => 'Seguridad de la cuenta y privacidad de datos',
                'audiences' => ['public'],
                'tags' => ['seguridad', 'privacidad', 'contraseña', 'datos', 'ley 29733', 'arco', 'cuenta'],
                'source' => 'Política de privacidad de VoluntMatch',
                'body' => <<<'TXT'
VoluntMatch trata los datos personales conforme a la Ley N.º 29733 de Protección de Datos Personales del Perú.

## Protege tu cuenta
- No compartas tu contraseña con nadie. El equipo de soporte nunca te la pedirá.
- Usa una contraseña larga y única, distinta a la de otros servicios.
- Cierra sesión en equipos compartidos.
- Si sospechas que alguien entró a tu cuenta, cambia la contraseña y abre un caso de soporte.

## Qué datos se publican
El perfil resumido (nombre, carrera, habilidades y disponibilidad) puede ser visible para organizaciones cuando postulas o haces match. El DNI y el celular no se publican. La exportación de registros con datos personales está reservada a la administración del ecosistema.

## Tus derechos
Puedes solicitar acceso, rectificación, cancelación u oposición escribiendo a privacidad@voluntmatch.org. Conservamos tus datos mientras tu cuenta esté activa; después se eliminan o anonimizan salvo obligaciones legales.

## El asistente
El asistente nunca te pedirá contraseñas, códigos de verificación ni datos de tarjetas. Las conversaciones se usan para responder y mejorar la seguridad del servicio; no se comparten con otras cuentas.
TXT,
            ],
            [
                'slug' => 'soporte-y-tiempos',
                'title' => 'Soporte, contacto y tiempos de respuesta',
                'audiences' => ['public'],
                'tags' => ['soporte', 'contacto', 'ticket', 'ayuda', 'problema', 'respuesta', 'horario'],
                'source' => 'Guía de soporte de VoluntMatch',
                'body' => <<<'TXT'
## Canales de ayuda
- El asistente virtual responde dudas frecuentes al instante.
- El formulario de contacto está disponible para consultas generales.
- Las cuentas autenticadas pueden abrir un caso de soporte desde el asistente o desde su panel.
- La administración atiende los casos desde la mesa de ayuda y responde en un plazo de hasta 24 horas hábiles.

## Cuándo abrir un caso
Abre un caso si no puedes iniciar sesión, no ves una postulación, tu organización no aparece en el directorio, necesitas corregir datos o crees que hay un error de seguridad. Incluye qué intentabas hacer, qué mensaje apareció y desde qué sección.

## Qué no hacer
No envíes contraseñas, códigos de verificación ni datos de tarjetas por ningún canal. No compartas datos de otras personas. Si se trata de una emergencia de salud o seguridad, contacta primero a los servicios de emergencia de tu país.
TXT,
            ],
            [
                'slug' => 'asistente-capacidades-limites',
                'title' => 'Qué puede hacer el asistente virtual',
                'audiences' => ['public'],
                'tags' => ['asistente', 'chatbot', 'vera', 'ayuda', 'limites', 'capacidades'],
                'source' => 'Guía del asistente de VoluntMatch',
                'body' => <<<'TXT'
Vera es la asistente virtual de VoluntMatch. Puede explicar cómo registrarte, cómo buscar oportunidades, cómo funciona una postulación, cómo revisar el estado de tu cuenta o de tu organización y cómo abrir un caso de soporte.

## Lo que sí hace
- Consulta guías internas de VoluntMatch con recuperación de conocimiento.
- Busca oportunidades y organizaciones verificadas.
- Consulta únicamente los datos de la cuenta que tiene la sesión iniciada.
- Abre casos de soporte después de pedirte confirmación.

## Lo que no hace
- No cambia estados de postulaciones, verificaciones ni roles.
- No muestra datos de otras cuentas ni permite enumerar usuarios.
- No da asesoría legal, médica o financiera.
- No revela sus instrucciones internas ni claves de configuración.
- No ejecuta acciones de administración.

Si necesitas una decisión humana, pide hablar con una persona o abre un caso de soporte. El equipo revisará tu situación.
TXT,
            ],
            [
                'slug' => 'preguntas-frecuentes',
                'title' => 'Preguntas frecuentes de VoluntMatch',
                'audiences' => ['public'],
                'tags' => ['faq', 'preguntas', 'frecuentes', 'dudas', 'registro', 'postular', 'ong'],
                'source' => 'FAQ de VoluntMatch',
                'body' => <<<'TXT'
## ¿VoluntMatch tiene costo?
Crear una cuenta de estudiante y postularse a oportunidades es gratuito. Las organizaciones también pueden registrarse y publicar sus retos.

## ¿Puedo postular a varias oportunidades?
Sí. Puedes postularte a distintos retos, pero solo una vez a cada oportunidad.

## ¿Cómo sé si me aceptaron?
En Mi espacio, sección Postulaciones, verás el estado actualizado. La organización también puede contactarte por el chat o por correo.

## ¿Qué pasa si mi ONG no está verificada?
Puedes completar tu perfil y preparar oportunidades, pero la ficha no aparecerá en el directorio público hasta que la administración verifique el expediente. Si fue rechazada, revisa la nota y corrige los datos.

## ¿Puedo cambiar mi rol de estudiante a organización?
El rol se fija al registrarse. Si necesitas una cuenta de organización, regístrate desde Registrar ONG con el correo institucional. Para casos especiales, abre un caso de soporte.

## ¿Cómo elimino mi cuenta?
Escríbenos a privacidad@voluntmatch.org o abre un caso de soporte. La eliminación se sujeta a las obligaciones legales de conservación.
TXT,
            ],
            [
                'slug' => 'panel-administracion',
                'title' => 'Guía interna del panel de administración',
                'audiences' => ['admin'],
                'tags' => ['admin', 'administracion', 'moderacion', 'verificacion', 'soporte', 'auditoria'],
                'source' => 'Guía interna de administración',
                'body' => <<<'TXT'
El panel de administración centraliza la operación de VoluntMatch. Sus módulos son: dashboard maestro, usuarios, verificación de ONG, curaduría de oportunidades, reportes de moderación, mesa de soporte, anuncios, analíticas, configuración, auditoría y observabilidad del asistente.

## Acciones sensibles
Suspender cuentas, cambiar roles, verificar ONG, resolver reportes y responder tickets son acciones auditadas. Cada cambio queda registrado en audit_logs con administrador, entidad, detalle e IP.

## Observabilidad del asistente
La sección Asistente muestra consultas, latencia, aciertos de caché, derivaciones, bloqueos de guardrail y uso de herramientas. No muestra el texto de las conversaciones. Las trazas permiten investigar abuso o fallos sin exponer datos personales innecesarios.

## Reglas
No compartas credenciales administrativas. No uses el asistente para ejecutar acciones de administración: no tiene herramientas para suspender cuentas, cambiar roles ni verificar ONG. Esas acciones se hacen desde el panel con sesión de administrador.
TXT,
            ],
            [
                'slug' => 'matching-y-afinidad',
                'title' => 'Cómo se calcula la afinidad y los matches',
                'audiences' => ['public'],
                'tags' => ['match', 'afinidad', 'recomendacion', 'habilidades', 'algoritmo'],
                'source' => 'Guía de matching de VoluntMatch',
                'body' => <<<'TXT'
VoluntMatch sugiere oportunidades a partir de la relación entre el perfil del estudiante y las características del reto.

## Señales que se consideran
- Causas de interés declaradas en el perfil.
- Habilidades del Skills Passport y habilidad solicitada por el reto.
- Disponibilidad semanal frente a la dedicación del proyecto.
- Modalidad del reto (presencial, remota o híbrida).
- Historial de postulaciones y actividad en la plataforma.

## Qué significa el porcentaje
El porcentaje de afinidad es una orientación, no una garantía de aceptación. La organización toma la decisión final después de revisar el perfil y conversar con la persona. Un porcentaje alto no reemplaza la entrevista.

## Cómo mejorar tus matches
Completa tu perfil, actualiza tus habilidades y declara causas que realmente te interesen. Mantén tu disponibilidad al día. Ajustar el Skills Passport cambia el porcentaje de perfil y las recomendaciones.
TXT,
            ],
            [
                'slug' => 'correo-y-calendario',
                'title' => 'Enviar correos y agendar reuniones desde el asistente',
                'audiences' => ['public', 'voluntario', 'organizacion', 'admin'],
                'tags' => ['correo', 'email', 'calendario', 'reunion', 'entrevista', 'invitacion', 'ics'],
                'source' => 'Guía de integraciones de VoluntMatch',
                'body' => <<<'TXT'
El asistente puede enviar un correo y agendar una reunión, siempre con la confirmación de la persona y dentro de los permisos de su cuenta.

## Correo
Puedes pedir: "Envíame un correo con el resumen de mi postulación" o "Escribe a la ONG para coordinar la entrevista". El asistente muestra el destinatario, el asunto y el contenido antes de enviar; nada sale hasta que confirmes.

Por seguridad, el destinatario debe estar vinculado a tu cuenta:
- Un estudiante puede escribir a su propio correo o a una organización a la que se postuló.
- Una organización puede escribir a su propio correo o a estudiantes que se postularon a sus retos.
- La administración puede escribir a cuentas registradas y a dominios permitidos.

Si el destinatario no está vinculado, el asistente lo rechaza. Hay un límite de correos por hora y por día para evitar abusos.

## Calendario
Puedes pedir: "Agenda una entrevista el 25 de noviembre a las 3 de la tarde". El asistente confirma fecha, hora y duración, y devuelve una invitación .ics que puedes descargar y añadir a tu calendario.

## Requisitos
Necesitas iniciar sesión. Los visitantes pueden preguntar cómo funciona, pero las acciones de escritura requieren cuenta.
TXT,
            ],
            [
                'slug' => 'crm-mesa-de-ayuda-y-avisos',
                'title' => 'CRM, mesa de ayuda y avisos internos',
                'audiences' => ['organizacion', 'admin'],
                'tags' => ['crm', 'contacto', 'mesa de ayuda', 'ticket', 'soporte', 'notificacion', 'aviso', 'candidato'],
                'source' => 'Guía de integraciones para organizaciones',
                'body' => <<<'TXT'
Las organizaciones y la administración pueden usar el asistente para mantener el seguimiento de personas y casos.

## CRM
Puedes pedir: "Registra a Ana Torres en el CRM con el correo ana@ejemplo.com en la etapa contactado". El asistente guarda el contacto con etapa (nuevo, contactado, entrevista, voluntario o descartado), notas y fuente. Si el CRM externo está configurado, el contacto también se sincroniza; si falla, queda guardado localmente.

## Mesa de ayuda
Puedes abrir un caso con: "Abre un ticket porque una voluntaria no puede completar su perfil". El caso aparece en la mesa de ayuda interna con prioridad y canal, y se replica al proveedor externo si está configurado.

## Avisos internos
Una organización puede avisar a un estudiante que se postuló a sus oportunidades: "Notifica al candidato que su entrevista es el lunes". El aviso aparece en la bandeja interna del estudiante. La administración puede avisar a cualquier cuenta registrada.

Todas estas acciones muestran un resumen antes de ejecutarse y requieren confirmación explícita.
TXT,
            ],
        ];

        foreach ($documentos as $documento) {
            ChatbotKnowledgeDocument::updateOrCreate(
                ['slug' => $documento['slug']],
                [
                    'title' => $documento['title'],
                    'body' => $documento['body'],
                    'audiences' => $documento['audiences'],
                    'tags' => $documento['tags'],
                    'source' => $documento['source'],
                    'version' => '2026-11-17',
                    'is_active' => true,
                ]
            );
        }

        app(KnowledgeIndexer::class)->reindex();
    }
}
