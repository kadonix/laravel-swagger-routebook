<?php

namespace Kadonix\Routebook\Annotations;

use Kadonix\Routebook\Attributes\Endpoint;
use Kadonix\Routebook\Attributes\Example;
use Kadonix\Routebook\Attributes\Parameter;
use Kadonix\Routebook\Attributes\RequestBody;
use Kadonix\Routebook\Attributes\Response;
use ReflectionMethod;

final class DocblockParser
{
    /**
     * @return array{endpoint: Endpoint|null, parameters: array<int, Parameter>, requestBody: RequestBody|null, responses: array<int, Response>, examples: array<int, Example>}
     */
    public function parse(ReflectionMethod $method): array
    {
        $docblock = $method->getDocComment() ?: '';

        return [
            'endpoint' => $this->first($docblock, 'Endpoint', fn (array $values): Endpoint => new Endpoint(
                method: $values['method'] ?? null,
                path: $values['path'] ?? null,
                summary: $values['summary'] ?? null,
                description: $values['description'] ?? null,
                tags: $this->stringList($values['tags'] ?? []),
                group: $values['group'] ?? null,
                operationId: $values['operationId'] ?? null,
                deprecated: (bool) ($values['deprecated'] ?? false),
                auth: (bool) ($values['auth'] ?? false),
            )),
            'parameters' => $this->many($docblock, 'Parameter', fn (array $values): Parameter => new Parameter(
                name: $values['name'] ?? $values[0] ?? '',
                in: $values['in'] ?? 'query',
                type: $values['type'] ?? 'string',
                description: $values['description'] ?? null,
                required: (bool) ($values['required'] ?? false),
                example: $values['example'] ?? null,
            )),
            'requestBody' => $this->first($docblock, 'RequestBody', fn (array $values): RequestBody => new RequestBody(
                from: $this->className($values['from'] ?? null),
                required: (bool) ($values['required'] ?? true),
                contentType: $values['contentType'] ?? 'application/json',
                description: $values['description'] ?? null,
            )) ?? $this->first($docblock, 'Body', fn (array $values): RequestBody => new RequestBody(
                from: $this->className($values['from'] ?? $values[0] ?? null),
                required: (bool) ($values['required'] ?? true),
                contentType: $values['contentType'] ?? 'application/json',
                description: $values['description'] ?? null,
            )),
            'responses' => [
                ...$this->many($docblock, 'Response', fn (array $values): Response => new Response(
                status: $values['status'] ?? $values[0] ?? 200,
                description: $values['description'] ?? $values[1] ?? 'OK',
                from: $this->className($values['from'] ?? null),
                collection: (bool) ($values['collection'] ?? false),
                contentType: $values['contentType'] ?? 'application/json',
                )),
                ...$this->many($docblock, 'Returns', fn (array $values): Response => new Response(
                    status: $values['status'] ?? 200,
                    description: $values['description'] ?? 'OK',
                    from: $this->className($values['from'] ?? $values[0] ?? null),
                    collection: (bool) ($values['collection'] ?? false),
                    contentType: $values['contentType'] ?? 'application/json',
                )),
            ],
            'examples' => $this->many($docblock, 'Example', fn (array $values): Example => new Example(
                name: $values['name'] ?? $values[0] ?? 'Example',
                type: $values['type'] ?? 'response',
                status: $values['status'] ?? 200,
                value: $this->exampleValue($values['value'] ?? null),
            )),
        ];
    }

    /**
     * @template T
     * @param callable(array<string|int, mixed>): T $factory
     * @return T|null
     */
    private function first(string $docblock, string $name, callable $factory): mixed
    {
        $items = $this->many($docblock, $name, $factory);

        return $items[0] ?? null;
    }

    /**
     * @template T
     * @param callable(array<string|int, mixed>): T $factory
     * @return array<int, T>
     */
    private function many(string $docblock, string $name, callable $factory): array
    {
        preg_match_all('/@' . preg_quote($name, '/') . '\s*(?:\(([^)]*)\))?/', $docblock, $matches);

        return array_map(fn (string $raw): mixed => $factory($this->arguments($raw)), $matches[1] ?? []);
    }

    /**
     * @return array<string|int, mixed>
     */
    private function arguments(string $raw): array
    {
        $arguments = [];
        $position = 0;

        preg_match_all('/(\w+\s*=\s*)?(\"[^\"]*\"|\'[^\']*\'|\{[^}]*}|[^,\s)]+)/', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = isset($match[1]) && trim($match[1]) !== '' ? rtrim(trim($match[1]), '= ') : $position++;
            $arguments[$key] = $this->value($match[2]);
        }

        return $arguments;
    }

    private function value(string $raw): mixed
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $items = trim($raw, '{} ');

            if ($items === '') {
                return [];
            }

            return array_map(fn (string $item): mixed => $this->value($item), explode(',', $items));
        }

        if (($raw[0] ?? '') === '"' || ($raw[0] ?? '') === "'") {
            return trim($raw, "\"'");
        }

        return match (strtolower($raw)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($raw) ? $raw + 0 : $raw,
        };
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        if ($value === null || $value === '') {
            return [];
        }

        return array_map('trim', explode(',', (string) $value));
    }

    private function className(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return str_ends_with($value, '::class') ? substr($value, 0, -7) : $value;
    }

    private function exampleValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
