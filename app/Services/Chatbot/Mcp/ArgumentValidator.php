<?php

namespace App\Services\Chatbot\Mcp;

/**
 * Validador mínimo de JSON Schema para argumentos de herramientas.
 *
 * No intenta ser un validador completo: cubre objetos planos con tipos,
 * enums, límites y listas. Es deliberadamente estricto y rechaza claves
 * desconocidas para evitar que un modelo inyecte parámetros internos.
 */
class ArgumentValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $input
     * @return array{valid: bool, args: array<string, mixed>, errors: list<string>}
     */
    public function validate(array $schema, array $input): array
    {
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $allowExtra = (bool) ($schema['additionalProperties'] ?? false);

        $args = [];
        $errors = [];

        if (! $allowExtra) {
            foreach (array_keys($input) as $key) {
                if (! array_key_exists($key, $properties)) {
                    $errors[] = "El parámetro «{$key}» no está permitido.";
                }
            }
        }

        foreach ($properties as $name => $rules) {
            if (! is_array($rules)) {
                continue;
            }

            $provided = array_key_exists($name, $input);

            if (! $provided) {
                if (array_key_exists('default', $rules)) {
                    $args[$name] = $rules['default'];
                } elseif (in_array($name, $required, true)) {
                    $errors[] = "Falta el parámetro «{$name}».";
                }

                continue;
            }

            $value = $input[$name];
            $type = $rules['type'] ?? 'string';

            if ($value === null) {
                if (in_array($name, $required, true)) {
                    $errors[] = "El parámetro «{$name}» es obligatorio.";
                }

                continue;
            }

            $coerced = $this->coerce($type, $value, $name, $errors);
            if ($coerced === null && $type !== 'null') {
                continue;
            }

            if ($type === 'string') {
                $max = (int) ($rules['maxLength'] ?? 1000);
                $coerced = mb_substr(trim((string) $coerced), 0, $max);
                $min = (int) ($rules['minLength'] ?? 0);
                if (mb_strlen($coerced) < $min) {
                    $errors[] = "El parámetro «{$name}» es demasiado corto.";

                    continue;
                }
                $coerced = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $coerced) ?? $coerced;
            }

            if ($type === 'integer') {
                $min = $rules['minimum'] ?? null;
                $max = $rules['maximum'] ?? null;
                if ($min !== null && $coerced < $min) {
                    $coerced = (int) $min;
                }
                if ($max !== null && $coerced > $max) {
                    $coerced = (int) $max;
                }
            }

            if ($type === 'array') {
                $items = is_array($rules['items'] ?? null) ? $rules['items'] : ['type' => 'string'];
                $maxItems = (int) ($rules['maxItems'] ?? 10);
                $coerced = array_slice(array_values((array) $coerced), 0, $maxItems);
                $coerced = array_map(function ($item) use ($items, $name, &$errors) {
                    $localErrors = [];
                    $value = $this->coerce((string) ($items['type'] ?? 'string'), $item, $name, $localErrors);
                    if ($localErrors !== []) {
                        $errors = array_merge($errors, $localErrors);
                    }

                    return is_string($value) ? mb_substr(trim($value), 0, 120) : $value;
                }, $coerced);
            }

            if (isset($rules['enum']) && is_array($rules['enum']) && ! in_array($coerced, $rules['enum'], true)) {
                $errors[] = "El valor de «{$name}» no es válido.";

                continue;
            }

            $args[$name] = $coerced;
        }

        return [
            'valid' => $errors === [],
            'args' => $args,
            'errors' => $errors,
        ];
    }

    private function coerce(string $type, mixed $value, string $name, array &$errors): mixed
    {
        return match ($type) {
            'string' => is_scalar($value) ? (string) $value : $this->fail($name, 'texto', $errors),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : $this->fail($name, 'número entero', $errors),
            'boolean' => is_bool($value)
                ? $value
                : (in_array($value, [1, '1', 'true', true], true) ? true : (in_array($value, [0, '0', 'false', false], true) ? false : $this->fail($name, 'booleano', $errors))),
            'array' => is_array($value) ? $value : $this->fail($name, 'lista', $errors),
            default => $value,
        };
    }

    private function fail(string $name, string $expected, array &$errors): mixed
    {
        $errors[] = "El parámetro «{$name}» debe ser {$expected}.";

        return null;
    }
}
