<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

/**
 * Guardrails de entrada y salida.
 *
 * La entrada se inspecciona antes de tocar RAG o herramientas. La salida
 * se inspecciona antes de devolverla al navegador. Los mensajes de bloqueo
 * son genéricos a propósito: no repiten el contenido malicioso ni confirman
 * qué regla exacta se activó.
 */
class GuardrailService
{
    /** Patrones de manipulación del prompt (español e inglés). */
    private const INJECTION_PATTERNS = [
        '/ignora\s+(?:todas?\s+)?(?:tus|las|mis)\s+(?:instrucciones|reglas|indicaciones)/iu',
        '/olvida(?:te)?\s+(?:de\s+)?(?:tus|las)\s+(?:instrucciones|reglas)/iu',
        '/(?:revela|muestra|imprime|repite|dime|escribe|cu[eé]ntame)\s+(?:tu|el)\s+(?:system\s*prompt|prompt\s+(?:del\s+)?sistema|instrucciones\s+(?:internas|del\s+sistema)?)/iu',
        '/\b(?:system\s*prompt|prompt\s+del\s+sistema|instrucciones\s+internas)\b/iu',
        '/(?:jailbreak|modo\s+desarrollador|developer\s+mode|do\s+anything\s+now)/iu',
        '/(?:act[uú]a|comp[oó]rtate|finge)\s+como\s+(?:un\s+)?(?:administrador|admin|root|otro\s+modelo|chatgpt|sistema)/iu',
        '/(?:ignore|disregard|forget)\s+(?:all\s+)?(?:your\s+)?(?:previous\s+)?(?:instructions|rules)/i',
        '/(?:repeat|print|show|reveal)\s+(?:your\s+)?(?:system\s+prompt|instructions)/i',
        '/<\s*\|?\s*(?:system|assistant|developer)\s*\|?\s*>/i',
        '/\[(?:system|developer|instrucciones)\]/iu',
        '/(?:soy|act[uú]a\s+como)\s+(?:el\s+)?(?:admin|administrador)\s+(?:y|de)\s+(?:la\s+)?plataforma/iu',
    ];

    private const ILLEGAL_PATTERNS = [
        '/(?:c[oó]mo\s+)?(?:hackear|robar|falsificar|suplantar)\s+(?:una\s+)?(?:cuenta|identidad|documento)/iu',
        '/(?:crear|fabricar|generar)\s+(?:documentos?|t[ií]tulos?|certificados?)\s+falsos?/iu',
        '/(?:vulnerar|bypass|saltarse)\s+(?:la\s+)?(?:seguridad|autenticaci[oó]n|cuenta)/iu',
        '/(?:doxxear|acosar|amenazar)\s+a\s+(?:una\s+)?persona/iu',
    ];

    private const SELF_HARM_PATTERNS = [
        '/\b(?:suicid(?:io|arme)|autolesi[oó]n|autolesionarme|quitarme\s+la\s+vida|acabar\s+con\s+mi\s+vida)\b/iu',
    ];

    private const PASSWORD_PATTERNS = [
        '/\b(?:contrase[nñ]a|password|clave)\s*(?:es|:|=)\s*\S{4,}/iu',
    ];

    /**
     * Inspecciona y normaliza el mensaje del usuario.
     *
     * @return array{
     *     text: string,
     *     allowed: bool,
     *     action: string,
     *     flags: list<string>,
     *     reason: ?string
     * }
     */
    public function inspectInput(string $text, string $audience = 'public'): array
    {
        $text = $this->normalizar($text);
        $flags = [];

        if ($text === '') {
            return [
                'text' => '',
                'allowed' => false,
                'action' => 'block',
                'flags' => ['empty'],
                'reason' => 'empty',
            ];
        }

        $max = (int) config('chatbot.prompt.max_input_chars', 1200);
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
            $flags[] = 'truncated';
        }

        foreach (self::SELF_HARM_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'text' => $text,
                    'allowed' => true,
                    'action' => 'handoff',
                    'flags' => ['self_harm'],
                    'reason' => 'self_harm',
                ];
            }
        }

        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'text' => $text,
                    'allowed' => false,
                    'action' => 'block',
                    'flags' => ['prompt_injection'],
                    'reason' => 'prompt_injection',
                ];
            }
        }

        foreach (self::ILLEGAL_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'text' => $text,
                    'allowed' => false,
                    'action' => 'block',
                    'flags' => ['illegal'],
                    'reason' => 'illegal',
                ];
            }
        }

        foreach (self::PASSWORD_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                $flags[] = 'password_shared';
                break;
            }
        }

        if ($this->contienePii($text)) {
            $flags[] = 'pii_detected';
        }

        return [
            'text' => $text,
            'allowed' => true,
            'action' => 'allow',
            'flags' => $flags,
            'reason' => null,
        ];
    }

    /**
     * Inspecciona la respuesta antes de enviarla y elimina fugas obvias.
     *
     * @return array{text: string, flags: list<string>, changed: bool}
     */
    public function inspectOutput(string $text): array
    {
        $original = $text;
        $flags = [];

        $text = $this->normalizar($text);

        $canary = (string) config('chatbot.prompt.canary');
        if ($canary !== '' && Str::contains($text, $canary, true)) {
            $text = 'No puedo compartir detalles internos de mi configuración. ¿Te ayudo con un trámite de VoluntMatch?';
            $flags[] = 'canary_leak';
        }

        if (preg_match('/(?:system\s*prompt|prompt\s+del\s+sistema|instrucciones\s+internas)/iu', $text)) {
            $text = 'No puedo compartir detalles internos de mi configuración. ¿Te ayudo con un trámite de VoluntMatch?';
            $flags[] = 'prompt_leak';
        }

        $secretPatterns = [
            '/\bsk[-_][A-Za-z0-9]{16,}\b/',
            '/\bBearer\s+[A-Za-z0-9._\-]{16,}/i',
            '/-----BEGIN [A-Z ]+PRIVATE KEY-----.*?-----END [A-Z ]+PRIVATE KEY-----/s',
            '/\b(?:api[_-]?key|token)\s*[:=]\s*[A-Za-z0-9._\-]{12,}/iu',
        ];

        foreach ($secretPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $text = preg_replace($pattern, '[dato protegido]', $text) ?? $text;
                $flags[] = 'secret_redacted';
            }
        }

        // La interfaz pinta texto plano: no se permite HTML ni esquemas activos.
        if (preg_match('/<\s*(?:script|iframe|object|embed|img|svg)\b/i', $text)) {
            $text = preg_replace('/<\s*(?:script|iframe|object|embed|img|svg)\b[^>]*>/iu', '', $text) ?? $text;
            $flags[] = 'html_removed';
        }

        $text = preg_replace('/javascript\s*:/iu', '', $text) ?? $text;
        $text = preg_replace('/on(?:error|load|click)\s*=/iu', '', $text) ?? $text;

        $max = (int) config('chatbot.prompt.max_output_chars', 2400);
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max).'…';
            $flags[] = 'truncated';
        }

        return [
            'text' => trim($text),
            'flags' => array_values(array_unique($flags)),
            'changed' => $text !== $original,
        ];
    }

    /**
     * Limpia controles invisibles y normaliza espacios sin destruir saltos
     * de línea útiles para listas.
     */
    public function normalizar(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public function contienePii(string $text): bool
    {
        return (bool) preg_match(
            '/\b(?:\d{8}|9\d{8}|[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})\b/iu',
            $text
        );
    }

    /**
     * Redacta datos sensibles para logs, trazas y argumentos de herramientas.
     * No se usa para el historial visible del usuario.
     */
    public function redact(string $text): string
    {
        $text = preg_replace('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/iu', '[correo]', $text) ?? $text;
        $text = preg_replace('/\b(?:\d[ -]?){13,16}\b/u', '[tarjeta]', $text) ?? $text;
        $text = preg_replace('/\b9\d{8}\b/u', '[celular]', $text) ?? $text;
        $text = preg_replace('/\b\d{8}\b/u', '[documento]', $text) ?? $text;
        $text = preg_replace('/\b(?:sk[-_]|Bearer\s+)[A-Za-z0-9._\-]{12,}\b/iu', '[token]', $text) ?? $text;
        $text = preg_replace('/((?:contrase[nñ]a|password|clave)\s*(?:es|:|=)\s*)\S+/iu', '$1[oculto]', $text) ?? $text;

        return $text;
    }

    public function ipHash(string $ip): string
    {
        if (! config('chatbot.observability.anonymize_ip', true)) {
            return substr(hash('sha256', $ip), 0, 32);
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
