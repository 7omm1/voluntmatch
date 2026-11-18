<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Router de intenciones.
 *
 * La primera capa es determinista y barata: protege las rutas críticas
 * (saludos, soporte, acciones) sin depender de un modelo. Solo si el texto
 * es ambiguo y el router LLM está activado se pide una clasificación extra.
 * En ningún caso el router autoriza herramientas: ToolExecutor vuelve a
 * comprobar rol, esquema y confirmación.
 */
class IntentRouter
{
    private const TOOLS = [
        'buscar_oportunidades',
        'listar_ongs',
        'resumen_plataforma',
        'consultar_mis_postulaciones',
        'listar_mis_oportunidades',
        'consultar_mi_organizacion',
        'consultar_estado_cuenta',
        'crear_ticket_soporte',
        'enviar_correo',
        'agendar_evento',
        'registrar_contacto_crm',
        'notificar_candidato',
        'consultar_mis_notificaciones',
    ];

    public function __construct(
        private readonly LlmClient $llm,
        private readonly PromptBuilder $prompts,
    ) {}

    /**
     * @return array{
     *     intent: string,
     *     route: string,
     *     tool: ?string,
     *     args: array<string, mixed>,
     *     missing_args: list<string>,
     *     reason: string
     * }
     */
    public function classify(string $message, string $audience): array
    {
        $raw = trim($message);
        $text = $this->normalizar($raw);
        $length = mb_strlen($text);

        if ($length <= 45 && preg_match('/^(hola|buenas|buen d[ií]a|buenas tardes|buenas noches|hey|qu[eé] tal)\b/u', $text)) {
            return $this->result('saludo', 'smalltalk', null, [], [], 'saludo');
        }

        if ($length <= 60 && preg_match('/^(gracias|muchas gracias|te agradezco|perfecto,? gracias|ok,? gracias)\b/u', $text)) {
            return $this->result('agradecimiento', 'smalltalk', null, [], [], 'agradecimiento');
        }

        if ($this->esFueraDeAlcance($text)) {
            return $this->result('fuera_de_alcance', 'refuse', null, [], [], 'fuera_de_alcance');
        }

        if ($this->pideHumano($text)) {
            return $this->result('handoff', 'handoff', null, [], [], 'solicita_humano');
        }

        $tool = $this->toolFor($text, $audience);

        if ($tool !== null) {
            [$args, $missing] = $this->argsFor($tool, $raw, $text);

            return $this->result('herramienta', 'tool', $tool, $args, $missing, 'accion_detectada');
        }

        if (config('chatbot.router.llm_fallback') && $this->llm->isConfigured() && $length > 10) {
            $llmResult = $this->classifyWithLlm($raw, $audience);

            if ($llmResult !== null) {
                return $llmResult;
            }
        }

        return $this->result('faq', 'rag', null, [], [], 'conocimiento');
    }

    private function toolFor(string $text, string $audience): ?string
    {
        $esConsultaRegistro = (bool) preg_match(
            '/\b(?:como|donde)\s+(?:me\s+)?(?:registro|registrar|creo|crear|abro|abrir)\b|\b(?:quiero|necesito)\s+(?:registrarme|registrar\s+mi|crear\s+mi|crear\s+una)\b|\bregistrar\s+(?:mi|una)\s+(?:ong|organizacion|cuenta)\b/u',
            $text
        );

        $esAccionCuenta = (bool) preg_match(
            '/\b(?:eliminar|borrar|cancelar|cerrar|desactivar)\s+(?:mi|la)\s+cuenta\b/u',
            $text
        );

        // Soporte: crear un caso requiere autenticación; el ejecutor lo valida.
        if (preg_match('/(?:crear|crea|abrir|abre|registrar|registra|levantar|levanta)\s+(?:un\s+)?(?:ticket|caso|incidencia)|necesito\s+(?:abrir\s+)?(?:un\s+)?(?:ticket|caso de soporte)/u', $text)) {
            return 'crear_ticket_soporte';
        }

        // Integraciones reales: correo, calendario, CRM y notificaciones.
        if (preg_match('/\b(?:mis|mi)\s+notificaciones\b/u', $text)) {
            return 'consultar_mis_notificaciones';
        }

        if (preg_match('/\b(?:env[ií]a|enviar|manda|mandar|escribe|escribir)\s+(?:un\s+)?(?:correo|email|e-mail)\b/u', $text)) {
            return 'enviar_correo';
        }

        if (preg_match('/\b(?:agenda|agendar|programa|programar|reserva|reservar)\s+(?:un\s+)?(?:evento|reuni[oó]n|entrevista|llamada|cita)\b/u', $text)) {
            return 'agendar_evento';
        }

        if (preg_match('/\bcrm\b/u', $text)
            && preg_match('/\b(?:registra|registrar|crea|crear|agrega|agregar|guarda|guardar|actualiza|actualizar)\b/u', $text)) {
            return 'registrar_contacto_crm';
        }

        if (preg_match('/\b(?:notifica|notificar|avisa|avisar|informa|informar)\b/u', $text)
            && preg_match('/\b(?:candidat[oa]s?|voluntari[oa]s?|postulantes?)\b/u', $text)) {
            return 'notificar_candidato';
        }

        if (preg_match('/\b(?:mis|mi)\s+postulaci[oó]n(?:es)?\b|\ben\s+qu[eé]\s+va\s+mi\s+postulaci[oó]n|\bestado\s+de\s+mi\s+postulaci[oó]n/u', $text)) {
            return 'consultar_mis_postulaciones';
        }

        if (preg_match('/\b(?:mis|mi)\s+(?:oportunidades|retos|publicaciones)\b/u', $text)) {
            return 'listar_mis_oportunidades';
        }

        if (! $esConsultaRegistro && preg_match('/\b(?:mi|de\s+mi)\s+(?:ong|organizaci[oó]n)\b|\bestado\s+de\s+(?:mi|la)\s+verificaci[oó]n/u', $text)) {
            return 'consultar_mi_organizacion';
        }

        if (! $esConsultaRegistro && ! $esAccionCuenta && preg_match('/\b(?:mi\s+cuenta|mi\s+rol|estado\s+de\s+mi\s+cuenta|mis\s+datos)\b/u', $text)) {
            return 'consultar_estado_cuenta';
        }

        if (preg_match('/\b(?:cu[aá]ntos|cu[aá]ntas|estad[ií]sticas|resumen\s+de\s+la\s+plataforma|impacto\s+de\s+la\s+plataforma)\b/u', $text)
            && preg_match('/\b(?:voluntarios|ongs|organizaciones|oportunidades|postulaciones|plataforma)\b/u', $text)) {
            return 'resumen_plataforma';
        }

        if (preg_match('/\b(?:busca|buscar|busco|encuentra|encontrar|recomienda|recomendar|sugiere|sugerir|ver|mostrar|muestra|listar|lista)\b/u', $text)
            && preg_match('/\b(?:oportunidades?|voluntariado|retos?|proyectos?)\b/u', $text)) {
            return 'buscar_oportunidades';
        }

        if (preg_match('/\b(?:busca|buscar|busco|ver|mostrar|muestra|listar|lista)\b/u', $text)
            && preg_match('/\b(?:ongs?|organizaciones|directorio)\b/u', $text)) {
            return 'listar_ongs';
        }

        // "Quiero ser voluntario en una ONG de educación" es una búsqueda,
        // no una pregunta genérica de registro.
        if (preg_match('/\b(?:voluntariado|voluntario)\b/u', $text)
            && preg_match('/\b(?:en|de|sobre)\s+(?:educaci[oó]n|salud|ambiente|animales|infancia|adultos|comunidad|tecnolog[ií]a|cultura|derechos)\b/u', $text)) {
            return 'buscar_oportunidades';
        }

        return null;
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function argsFor(string $tool, string $raw, string $text): array
    {
        return match ($tool) {
            'buscar_oportunidades' => [
                ['query' => Str::limit($this->searchQuery($raw), 120, ''), 'limit' => 5],
                [],
            ],
            'listar_ongs' => [
                ['query' => Str::limit($this->searchQuery($raw), 120, ''), 'limit' => 5],
                [],
            ],
            'crear_ticket_soporte' => $this->ticketArgs($raw, $text),
            'enviar_correo' => $this->mailArgs($raw, $text),
            'agendar_evento' => $this->calendarArgs($raw, $text),
            'registrar_contacto_crm' => $this->crmArgs($raw),
            'notificar_candidato' => $this->notificationArgs($raw),
            default => [[], []],
        };
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function ticketArgs(string $raw, string $text): array
    {
        $sinVerbo = preg_replace(
            '/(?:crear|crea|abrir|abre|registrar|registra|levantar|levanta)\s+(?:un\s+)?(?:ticket|caso|incidencia)|necesito\s+(?:abrir\s+)?(?:un\s+)?(?:ticket|caso de soporte)/iu',
            '',
            $raw
        ) ?? $raw;

        $detalle = trim($sinVerbo);

        if (mb_strlen($detalle) < 12) {
            return [[], ['asunto', 'mensaje']];
        }

        return [
            [
                'asunto' => Str::limit(Str::ucfirst($detalle), 120, ''),
                'mensaje' => Str::limit($raw, 2000, ''),
                'prioridad' => preg_match('/urgente|inmediato|cr[ií]tico|no\s+puedo\s+entrar/iu', $text) ? 'alta' : 'media',
            ],
            [],
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function mailArgs(string $raw, string $text): array
    {
        $sinVerbo = preg_replace(
            '/\b(?:env[ií]a|enviar|manda|mandar|escribe|escribir)\s+(?:un\s+)?(?:correo|email|e-mail)\b/iu',
            '',
            $raw
        ) ?? $raw;

        $detalle = trim($sinVerbo);
        $email = $this->extraerEmail($raw);

        if (mb_strlen($detalle) < 12) {
            return [[], ['asunto', 'mensaje']];
        }

        $args = [
            'asunto' => Str::limit(Str::ucfirst($detalle), 120, ''),
            'mensaje' => Str::limit($raw, 2000, ''),
        ];

        if ($email !== null) {
            $args['destinatario'] = $email;
        }

        return [$args, []];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function calendarArgs(string $raw, string $text): array
    {
        $sinVerbo = preg_replace(
            '/\b(?:agenda|agendar|programa|programar|reserva|reservar)\s+(?:un\s+)?(?:evento|reuni[oó]n|entrevista|llamada|cita)\b/iu',
            '',
            $raw
        ) ?? $raw;

        $titulo = trim($sinVerbo);
        $inicio = null;

        if (preg_match('/(\d{4}-\d{2}-\d{2})(?:[ T](\d{1,2}:\d{2}))?/u', $raw, $m)) {
            $inicio = $m[1].' '.($m[2] ?? '09:00');
        } elseif (preg_match('/\b(hoy|ma[nñ]ana)\b(?:\s+a\s+las?\s+(\d{1,2})(?::(\d{2}))?\s*(am|pm)?)?/iu', $raw, $m)) {
            $fecha = preg_match('/ma[nñ]ana/iu', $m[1]) ? 'tomorrow' : 'today';
            $hora = (int) ($m[2] ?? 9);
            $minutos = $m[3] ?? '00';
            if (isset($m[4]) && mb_strtolower($m[4]) === 'pm' && $hora < 12) {
                $hora += 12;
            }
            $inicio = Carbon::parse($fecha)->setTime($hora, (int) $minutos)->format('Y-m-d H:i');
        }

        if ($titulo === '' || mb_strlen($titulo) < 3) {
            $titulo = 'Reunión de voluntariado';
        }

        $args = ['titulo' => Str::limit($titulo, 160, '')];
        $missing = [];

        if ($inicio === null) {
            $missing[] = 'inicio';
        } else {
            $args['inicio'] = $inicio;
        }

        return [$args, $missing];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function crmArgs(string $raw): array
    {
        $email = $this->extraerEmail($raw);

        if ($email === null) {
            return [[], ['nombre', 'email']];
        }

        $nombre = trim((string) preg_replace('/\S+@\S+/', '', $raw));
        $nombre = trim((string) preg_replace('/\b(?:registra|registrar|crea|crear|agrega|agregar|guarda|guardar|actualiza|actualizar)\b|\b(?:un\s+)?(?:contacto|prospecto)\b|\b(?:en\s+)?(?:el\s+)?crm\b/iu', ' ', $nombre));
        $nombre = trim((string) preg_replace('/\s+/u', ' ', $nombre));

        if (mb_strlen($nombre) < 2) {
            $nombre = Str::ucfirst(Str::before($email, '@'));
        }

        return [[
            'nombre' => Str::limit($nombre, 160, ''),
            'email' => $email,
            'etapa' => 'nuevo',
            'fuente' => 'asistente',
        ], []];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function notificationArgs(string $raw): array
    {
        $email = $this->extraerEmail($raw);

        if ($email === null) {
            return [[], ['email']];
        }

        return [[
            'email' => $email,
            'titulo' => 'Aviso sobre tu postulación',
            'mensaje' => Str::limit($raw, 1000, ''),
        ], []];
    }

    private function extraerEmail(string $raw): ?string
    {
        return preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/iu', $raw, $m)
            ? mb_strtolower($m[0])
            : null;
    }

    private function searchQuery(string $raw): string
    {
        $query = preg_replace(
            '/\b(?:quiero|busco|busca|buscar|encontrar|encuentra|recomienda|sugiere|ver|mostrar|muestra|listar|lista|oportunidades?|voluntariado|retos?|proyectos?|ongs?|organizaciones|directorio|de|del|en|para|por|favor|me|gustar[ií]a)\b/iu',
            ' ',
            $raw
        ) ?? $raw;

        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;

        return trim($query) !== '' ? trim($query) : trim($raw);
    }

    private function pideHumano(string $text): bool
    {
        return (bool) preg_match(
            '/\b(?:hablar|comunicarme|contactar)\s+con\s+(?:una\s+)?(?:persona|humano|agente|alguien\s+real|un\s+humano)|soporte\s+humano|atenci[oó]n\s+humana|quiero\s+una\s+persona\b|\b(?:formulario\s+de\s+)?contacto\b/u',
            $text
        );
    }

    private function esFueraDeAlcance(string $text): bool
    {
        $dominios = [
            '/\b(?:asesor[ií]a|consulta)\s+(?:legal|jur[ií]dica|m[eé]dica|financiera)\b/u',
            '/\b(?:diagn[oó]stico|medicamento|dosis|receta\s+m[eé]dica|s[ií]ntomas?\s+de)\b/u',
            '/\b(?:invertir|criptomonedas?|pr[eé]stamo\s+bancario|acciones\s+de\s+bolsa)\b/u',
            '/\b(?:elecciones|pol[ií]tica\s+partidaria|candidato\s+presidencial)\b/u',
            '/\b(?:hacer|resolver)\s+(?:mi|una)\s+(?:tarea|examen)\b/u',
        ];

        foreach ($dominios as $patron) {
            if (preg_match($patron, $text)) {
                return true;
            }
        }

        return false;
    }

    private function classifyWithLlm(string $message, string $audience): ?array
    {
        try {
            $response = $this->llm->chat(
                [['role' => 'user', 'content' => $this->prompts->routerPrompt($message, $audience)]],
                [],
                $this->llm->modelFor('small')
            );
        } catch (\Throwable) {
            return null;
        }

        $content = trim($response['content']);
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            return null;
        }

        $intent = (string) ($data['intent'] ?? 'otro');
        $tool = $data['tool'] ?? 'ninguna';

        if ($intent === 'herramienta' && in_array($tool, self::TOOLS, true)) {
            [$args, $missing] = $this->argsFor($tool, $message, $this->normalizar($message));

            return $this->result('herramienta', 'tool', $tool, $args, $missing, 'llm_router');
        }

        return match ($intent) {
            'saludo' => $this->result('saludo', 'smalltalk', null, [], [], 'llm_router'),
            'agradecimiento' => $this->result('agradecimiento', 'smalltalk', null, [], [], 'llm_router'),
            'handoff' => $this->result('handoff', 'handoff', null, [], [], 'llm_router'),
            'fuera_de_alcance' => $this->result('fuera_de_alcance', 'refuse', null, [], [], 'llm_router'),
            'faq' => $this->result('faq', 'rag', null, [], [], 'llm_router'),
            default => $this->result('otro', 'rag', null, [], [], 'llm_router'),
        };
    }

    private function normalizar(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = Str::ascii($text);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /**
     * @param  array<string, mixed>  $args
     * @param  list<string>  $missing
     * @return array{intent: string, route: string, tool: ?string, args: array<string, mixed>, missing_args: list<string>, reason: string}
     */
    private function result(string $intent, string $route, ?string $tool, array $args, array $missing, string $reason): array
    {
        return [
            'intent' => $intent,
            'route' => $route,
            'tool' => $tool,
            'args' => $args,
            'missing_args' => $missing,
            'reason' => $reason,
        ];
    }
}
