<?php

namespace App\Services\Chatbot\Mcp;

/**
 * Catálogo de herramientas y matriz de acceso.
 *
 * Cada definición declara explícitamente qué audiencias pueden usarla.
 * Las herramientas de escritura requieren confirmación. Las herramientas
 * remotas solo existen si el servidor MCP está configurado y la herramienta
 * aparece en su allowlist.
 */
class ToolRegistry
{
    public function __construct(private readonly McpManager $mcp) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(string $audience): array
    {
        $tools = [];

        foreach ($this->localDefinitions() as $name => $definition) {
            if ($this->allowed($definition, $audience)) {
                $tools[$name] = $definition;
            }
        }

        foreach ($this->mcp->definitions($audience) as $name => $definition) {
            if ($this->allowed($definition, $audience)) {
                $tools[$name] = $definition;
            }
        }

        return $tools;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $name, string $audience): ?array
    {
        return $this->all($audience)[$name] ?? null;
    }

    /**
     * Definición local sin filtrar por audiencia. Se usa solo para decidir
     * si una denegación debe explicar que hace falta iniciar sesión.
     *
     * @return array<string, mixed>|null
     */
    public function definitionRegardlessOfAudience(string $name): ?array
    {
        return $this->localDefinitions()[$name] ?? null;
    }

    /**
     * @return list<string>
     */
    public function names(string $audience): array
    {
        return array_keys($this->all($audience));
    }

    /**
     * Definiciones en el formato de function calling de OpenAI.
     *
     * @return list<array<string, mixed>>
     */
    public function llmTools(string $audience): array
    {
        $tools = [];

        foreach ($this->all($audience) as $name => $definition) {
            if (($definition['expose_to_llm'] ?? true) === false) {
                continue;
            }

            $schema = $definition['input_schema'] ?? [
                'type' => 'object',
                'properties' => [],
                'additionalProperties' => false,
            ];

            if (isset($schema['properties']) && $schema['properties'] === []) {
                $schema['properties'] = (object) [];
            }

            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => (string) ($definition['description'] ?? ''),
                    'parameters' => $schema,
                ],
            ];
        }

        return $tools;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function allowed(array $definition, string $audience): bool
    {
        $audiences = $definition['audiences'] ?? ['admin'];

        return in_array('public', $audiences, true) || in_array($audience, $audiences, true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function localDefinitions(): array
    {
        $public = ['public', 'voluntario', 'organizacion', 'admin'];
        $auth = ['voluntario', 'organizacion', 'admin'];

        return [
            'buscar_oportunidades' => [
                'server' => 'local',
                'description' => 'Busca oportunidades de voluntariado publicadas por causa, habilidad, modalidad o palabra clave. Solo devuelve datos públicos.',
                'audiences' => $public,
                'requires_auth' => false,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'maxLength' => 120],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 8, 'default' => 5],
                    ],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'listar_ongs' => [
                'server' => 'local',
                'description' => 'Lista organizaciones verificadas del directorio público, filtrando por nombre, sector o ciudad.',
                'audiences' => $public,
                'requires_auth' => false,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'maxLength' => 120],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 8, 'default' => 5],
                    ],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'resumen_plataforma' => [
                'server' => 'local',
                'description' => 'Devuelve cifras públicas agregadas de VoluntMatch: oportunidades, ONG, voluntarios y postulaciones.',
                'audiences' => $public,
                'requires_auth' => false,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'consultar_mis_postulaciones' => [
                'server' => 'local',
                'description' => 'Consulta las postulaciones de la cuenta autenticada y su estado actual. No acepta el id de otro usuario.',
                'audiences' => ['voluntario'],
                'requires_auth' => true,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'listar_mis_oportunidades' => [
                'server' => 'local',
                'description' => 'Lista las oportunidades de la organización autenticada. Se resuelve por la cuenta de sesión, nunca por un id enviado por el modelo.',
                'audiences' => ['organizacion'],
                'requires_auth' => true,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'consultar_mi_organizacion' => [
                'server' => 'local',
                'description' => 'Muestra el estado de verificación y las métricas básicas de la organización autenticada.',
                'audiences' => ['organizacion'],
                'requires_auth' => true,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'consultar_estado_cuenta' => [
                'server' => 'local',
                'description' => 'Devuelve el rol y el estado de la cuenta autenticada, sin datos sensibles.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'crear_ticket_soporte' => [
                'server' => 'local',
                'description' => 'Abre un caso en la mesa de ayuda para la cuenta autenticada. Es una acción de escritura y requiere confirmación explícita del usuario.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => true,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'asunto' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 160],
                        'mensaje' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 2000],
                        'prioridad' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja'], 'default' => 'media'],
                    ],
                    'required' => ['asunto', 'mensaje'],
                    'additionalProperties' => false,
                ],
            ],
            'enviar_correo' => [
                'server' => 'local',
                'description' => 'Envía un correo real desde la plataforma al propio correo del usuario o a una persona vinculada a sus proyectos. Requiere confirmación explícita.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => true,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'asunto' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 160],
                        'mensaje' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 4000],
                        'destinatario' => ['type' => 'string', 'maxLength' => 180],
                    ],
                    'required' => ['asunto', 'mensaje'],
                    'additionalProperties' => false,
                ],
            ],
            'agendar_evento' => [
                'server' => 'local',
                'description' => 'Agenda una reunión o entrevista en el calendario y devuelve una invitación descargable. Requiere confirmación explícita.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => true,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 160],
                        'inicio' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 40],
                        'duracion_min' => ['type' => 'integer', 'minimum' => 15, 'maximum' => 480, 'default' => 60],
                        'descripcion' => ['type' => 'string', 'maxLength' => 2000],
                    ],
                    'required' => ['titulo', 'inicio'],
                    'additionalProperties' => false,
                ],
            ],
            'registrar_contacto_crm' => [
                'server' => 'local',
                'description' => 'Registra o actualiza un contacto en el CRM de la organización con su etapa y notas. Solo para organizaciones y administración; requiere confirmación.',
                'audiences' => ['organizacion', 'admin'],
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => true,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 160],
                        'email' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 180],
                        'telefono' => ['type' => 'string', 'maxLength' => 30],
                        'etapa' => ['type' => 'string', 'enum' => ['nuevo', 'contactado', 'entrevista', 'voluntario', 'descartado'], 'default' => 'nuevo'],
                        'fuente' => ['type' => 'string', 'maxLength' => 60],
                        'notas' => ['type' => 'string', 'maxLength' => 1500],
                    ],
                    'required' => ['nombre', 'email'],
                    'additionalProperties' => false,
                ],
            ],
            'notificar_candidato' => [
                'server' => 'local',
                'description' => 'Envía una notificación interna a un voluntario que se postuló a la organización (o a cualquier cuenta registrada si eres administración). Requiere confirmación.',
                'audiences' => ['organizacion', 'admin'],
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => true,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'email' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 180],
                        'titulo' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 120],
                        'mensaje' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 1000],
                    ],
                    'required' => ['email', 'titulo', 'mensaje'],
                    'additionalProperties' => false,
                ],
            ],
            'consultar_mis_notificaciones' => [
                'server' => 'local',
                'description' => 'Consulta las notificaciones internas de la cuenta autenticada, incluidas las enviadas desde el CRM o la mesa de ayuda.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => true,
                'requires_confirmation' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ],
            ],
            'confirmar_accion' => [
                'server' => 'local',
                'description' => 'Confirma una acción pendiente usando el token de un solo uso que devolvió otra herramienta. Exclusivo para clientes API/MCP.',
                'audiences' => $auth,
                'requires_auth' => true,
                'read_only' => false,
                'requires_confirmation' => false,
                'expose_to_llm' => false,
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'token' => ['type' => 'string', 'minLength' => 16, 'maxLength' => 128],
                    ],
                    'required' => ['token'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
