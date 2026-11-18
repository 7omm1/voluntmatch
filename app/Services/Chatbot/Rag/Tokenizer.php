<?php

namespace App\Services\Chatbot\Rag;

use Illuminate\Support\Str;

/**
 * Tokenizador ligero para español, sin dependencias externas.
 * Normaliza acentos, elimina palabras vacías y aplica un stem mínimo
 * (plurales) para que "postulaciones" y "postulación" se encuentren.
 */
class Tokenizer
{
    /** @var list<string> */
    private const STOPWORDS = [
        'a', 'al', 'algo', 'algunas', 'algunos', 'ante', 'antes', 'como', 'con',
        'contra', 'cual', 'cuando', 'de', 'del', 'desde', 'donde', 'dos', 'el',
        'ella', 'ellas', 'ellos', 'en', 'entre', 'era', 'es', 'esa', 'esas',
        'ese', 'eso', 'esos', 'esta', 'estas', 'este', 'esto', 'estos', 'fue',
        'ha', 'hasta', 'hay', 'la', 'las', 'le', 'les', 'lo', 'los', 'mas',
        'me', 'mi', 'mis', 'mucho', 'muy', 'no', 'nos', 'o', 'otra', 'otro',
        'para', 'pero', 'poco', 'por', 'porque', 'que', 'quien', 'se', 'ser',
        'si', 'sin', 'sobre', 'son', 'su', 'sus', 'te', 'tiene', 'tu', 'tus',
        'un', 'una', 'uno', 'unos', 'y', 'ya', 'yo', 'es', 'the', 'of', 'to',
        'and', 'in', 'is', 'it', 'for', 'on', 'with', 'this', 'that',
    ];

    /**
     * @return list<string>
     */
    public function tokens(string $text): array
    {
        $text = $this->normalizar($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];
        foreach ($parts as $part) {
            $token = $this->stem(mb_strtolower($part));

            if (mb_strlen($token) < 2) {
                continue;
            }

            if (preg_match('/^\d+$/u', $token)) {
                continue;
            }

            if (in_array($token, self::STOPWORDS, true)) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * Frecuencias de término usadas por el índice BM25.
     *
     * @return array<string, int>
     */
    public function termFrequencies(string $text): array
    {
        $frequencies = [];

        foreach ($this->tokens($text) as $token) {
            $frequencies[$token] = ($frequencies[$token] ?? 0) + 1;
        }

        return $frequencies;
    }

    public function normalizar(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = Str::ascii($text);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    private function stem(string $token): string
    {
        if (mb_strlen($token) <= 4) {
            return $token;
        }

        foreach (['mente', 'ciones', 'cion', 'idades', 'idad', 'es', 's', 'ar', 'er', 'ir'] as $suffix) {
            if (str_ends_with($token, $suffix) && mb_strlen($token) - mb_strlen($suffix) >= 4) {
                $token = mb_substr($token, 0, -mb_strlen($suffix));

                break;
            }
        }

        // Recorte suave: mejora la coincidencia entre sustantivos y verbos
        // de la misma familia (registro/registrar, verificación/verificar).
        return mb_strlen($token) > 6 ? mb_substr($token, 0, 6) : $token;
    }
}
