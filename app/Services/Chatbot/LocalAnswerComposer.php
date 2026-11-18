<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Compositor de respuestas para el modo local (sin proveedor LLM).
 *
 * Es extractivo: solo usa fragmentos recuperados y resultados de
 * herramientas. No inventa datos ni completa huecos con conocimiento
 * general. En modo openai este compositor se usa como respaldo si el
 * proveedor falla.
 */
class LocalAnswerComposer
{
    /**
     * @param  list<array<string, mixed>>  $chunks
     * @return array{text: string, suggestions: list<string>}
     */
    public function rag(array $chunks, string $audience): array
    {
        if ($chunks === []) {
            return [
                'text' => 'No encontré esa información en la documentación de VoluntMatch. Para no darte datos incorrectos, prefiero derivarte: puedo abrir un caso de soporte o puedes escribirnos desde la página de contacto.',
                'suggestions' => $this->suggestions($audience, 'handoff'),
            ];
        }

        $principal = $chunks[0];
        $texto = $this->limpiarFuente((string) $principal['content']);
        $texto = $this->recortarSentido($texto, 620);

        $respuesta = 'Según la guía «'.$principal['title'].'»:'.PHP_EOL.PHP_EOL.$texto;

        if (isset($chunks[1]) && (string) $chunks[1]['title'] !== (string) $principal['title']
            && (float) $chunks[1]['score'] >= (float) $principal['score'] * 0.65) {
            $respuesta .= PHP_EOL.PHP_EOL.'También puede ayudarte la guía «'.$chunks[1]['title'].'».';
        }

        $respuesta .= PHP_EOL.PHP_EOL.'¿Quieres que te indique el siguiente paso?';

        return [
            'text' => $respuesta,
            'suggestions' => $this->suggestions($audience, 'rag'),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{text: string, suggestions: list<string>}
     */
    public function tool(array $result, string $audience): array
    {
        $text = (string) ($result['summary'] ?? 'No obtuve resultados.');

        $items = $result['data']['items'] ?? [];
        if (is_array($items) && $items !== []) {
            $lines = [];
            foreach (array_slice($items, 0, 6) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $title = (string) ($item['title'] ?? '');
                $meta = (string) ($item['meta'] ?? '');
                $lines[] = '• '.$title.($meta !== '' ? ' — '.$meta : '');
            }

            if ($lines !== []) {
                $text .= PHP_EOL.PHP_EOL.implode(PHP_EOL, $lines);
            }
        }

        return [
            'text' => $text,
            'suggestions' => $this->suggestions($audience, 'tool'),
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function smalltalk(string $intent, string $audience, ?User $user = null): array
    {
        $nombre = $user?->name ? Str::of($user->name)->explode(' ')->filter()->first() : null;

        if ($intent === 'agradecimiento') {
            return [
                'text' => '¡De nada'.($nombre ? ', '.$nombre : '').'! Si necesitas otra cosa, aquí estoy. También puedes escribir «soporte» si prefieres hablar con una persona del equipo.',
                'suggestions' => $this->suggestions($audience, 'smalltalk'),
            ];
        }

        return [
            'text' => 'Hola'.($nombre ? ', '.$nombre : '').'. Soy Vera, la asistente de VoluntMatch. Te puedo ayudar con el registro, encontrar oportunidades, revisar tus postulaciones, la verificación de tu ONG o abrir un caso de soporte. ¿Qué necesitas?',
            'suggestions' => $this->suggestions($audience, 'smalltalk'),
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function handoff(string $audience, ?User $user = null): array
    {
        $text = 'Puedo derivarte con una persona del equipo de VoluntMatch. Si ya tienes cuenta, escríbeme «crea un ticket» y lo abro con el detalle de esta conversación. Si todavía no tienes cuenta, puedes usar el formulario de contacto y te responderemos en menos de 24 horas hábiles.';

        if ($audience === 'public') {
            $text .= ' También puedes crear una cuenta gratuita para dar seguimiento a tu caso desde tu panel.';
        }

        return [
            'text' => $text,
            'suggestions' => $audience === 'public'
                ? ['Ir al formulario de contacto', 'Iniciar sesión', 'Volver a las preguntas frecuentes']
                : ['Crear un ticket de soporte', 'Ver mis postulaciones', 'Volver a las preguntas frecuentes'],
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function refuse(string $audience): array
    {
        return [
            'text' => 'No puedo ayudar con asesoría legal, médica, financiera ni con temas ajenos a VoluntMatch. Sí puedo explicarte cómo registrarte, cómo funcionan las postulaciones o cómo contactar al equipo de soporte.',
            'suggestions' => $this->suggestions($audience, 'refuse'),
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function guardrailBlocked(string $reason, string $audience): array
    {
        if ($reason === 'self_harm') {
            return [
                'text' => 'Siento mucho que estés pasando por esto. No estás sola o solo. Si estás en peligro inmediato, llama a los servicios de emergencia de tu país (en Perú, el 911) o acude al centro de salud más cercano. Si puedes, habla ahora con una persona de confianza. Yo puedo ayudarte a contactar al equipo de VoluntMatch, pero no reemplazo la atención de un profesional.',
                'suggestions' => ['Quiero contactar a soporte', 'Volver al inicio'],
            ];
        }

        return [
            'text' => 'No puedo procesar esa solicitud porque intenta cambiar mis instrucciones o acceder a información interna. Puedo ayudarte con trámites y dudas de VoluntMatch: registro, oportunidades, postulaciones, ONG o soporte.',
            'suggestions' => $this->suggestions($audience, 'blocked'),
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function clarify(array $missing, string $audience): array
    {
        if (in_array('asunto', $missing, true) || in_array('mensaje', $missing, true)) {
            return [
                'text' => 'Claro. Para abrir el caso necesito que me cuentes brevemente qué ocurre y qué intentabas hacer. Por ejemplo: «No puedo completar mi perfil porque el botón de guardar no responde».',
                'suggestions' => ['Prefiero escribir a contacto'],
            ];
        }

        return [
            'text' => '¿Puedes darme un poco más de detalle para ayudarte mejor?',
            'suggestions' => $this->suggestions($audience, 'rag'),
        ];
    }

    /**
     * @return array{text: string, suggestions: list<string>}
     */
    public function error(string $audience): array
    {
        return [
            'text' => 'Tuve un problema al procesar tu mensaje. No se guardó ninguna acción. Puedes intentarlo de nuevo en unos segundos o escribir a soporte si el problema continúa.',
            'suggestions' => $this->suggestions($audience, 'handoff'),
        ];
    }

    /**
     * @return list<string>
     */
    public function suggestions(string $audience, string $context = ''): array
    {
        $base = match ($audience) {
            'voluntario' => ['Ver mis postulaciones', 'Buscar oportunidades', '¿Cómo completo mi perfil?'],
            'organizacion' => ['Estado de mi ONG', 'Mis oportunidades', '¿Cómo verifico mi ONG?'],
            'admin' => ['Resumen de la plataforma', 'Cómo funciona el asistente', 'Ver oportunidades'],
            default => ['¿Cómo me registro?', 'Buscar oportunidades', '¿Cómo registro mi ONG?'],
        };

        if ($context === 'handoff' || $context === 'blocked') {
            $base = array_slice($base, 0, 2);
            $base[] = 'Quiero hablar con soporte';
        }

        return array_values(array_unique(array_slice($base, 0, 3)));
    }

    private function limpiarFuente(string $text): string
    {
        $text = preg_replace('/^#{1,6}\s*/mu', '', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text) ?? $text;
        $text = preg_replace('/^\s*[-*]\s+/mu', '• ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function recortarSentido(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $corte = mb_substr($text, 0, $max);
        $ultimoPunto = max(
            (int) mb_strrpos($corte, '.'),
            (int) mb_strrpos($corte, "\n")
        );

        return trim($ultimoPunto > 80 ? mb_substr($corte, 0, $ultimoPunto + 1) : $corte).'…';
    }
}
