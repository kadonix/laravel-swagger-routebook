<?php

namespace Kadonix\Routebook\Schema;

use Illuminate\Foundation\Http\FormRequest;
use Kadonix\Routebook\Attributes\Schema as SchemaAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

final class SchemaFactory
{
    /**
     * @param array<string, mixed>|null $schema
     * @param class-string|null $from
     * @return array<string, mixed>
     */
    public function make(?array $schema = null, ?string $from = null): array
    {
        if ($schema !== null) {
            return $schema;
        }

        if ($from === null || ! class_exists($from)) {
            return ['type' => 'object'];
        }

        if (is_subclass_of($from, FormRequest::class)) {
            return $this->fromFormRequest($from);
        }

        return $this->fromClass($from);
    }

    public function nameFor(string $class): string
    {
        if (! class_exists($class)) {
            return class_basename($class);
        }

        $reflection = new ReflectionClass($class);
        $classSchema = $this->schemaAttribute($reflection->getAttributes(SchemaAttribute::class));

        return $classSchema?->name ?? $reflection->getShortName();
    }

    /**
     * @param class-string<FormRequest> $requestClass
     * @return array<string, mixed>
     */
    private function fromFormRequest(string $requestClass): array
    {
        /** @var FormRequest $request */
        $request = new $requestClass();
        $rules = method_exists($request, 'rules') ? $request->rules() : [];
        $properties = [];
        $required = [];

        foreach ($rules as $field => $ruleSet) {
            $rulesForField = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);
            $properties[$field] = $this->schemaFromRules($rulesForField);

            if ($this->containsRule($rulesForField, 'required')) {
                $required[] = $field;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param class-string $class
     * @return array<string, mixed>
     */
    private function fromClass(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $classSchema = $this->schemaAttribute($reflection->getAttributes(SchemaAttribute::class));
        $properties = $classSchema?->properties ?? [];
        $required = $classSchema?->required ?? [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $attribute = $this->schemaAttribute($property->getAttributes(SchemaAttribute::class));
            $properties[$property->getName()] = array_filter([
                'type' => $attribute?->type ?? $this->typeFromReflection($property->getType()),
                'description' => $attribute?->description,
                'example' => $attribute?->example,
            ], static fn (mixed $value): bool => $value !== null);
        }

        $schema = [
            'type' => $classSchema?->type ?? 'object',
            'properties' => $properties,
        ];

        if ($classSchema?->description !== null) {
            $schema['description'] = $classSchema->description;
        }

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param array<int, mixed> $rules
     * @return array<string, mixed>
     */
    private function schemaFromRules(array $rules): array
    {
        $schema = ['type' => 'string'];

        foreach ($rules as $rule) {
            $ruleName = is_object($rule) ? $rule::class : strtolower(strtok((string) $rule, ':') ?: (string) $rule);

            match ($ruleName) {
                'integer', 'int' => $schema['type'] = 'integer',
                'numeric' => $schema['type'] = 'number',
                'boolean', 'bool', 'accepted' => $schema['type'] = 'boolean',
                'array' => $schema['type'] = 'array',
                'date' => $schema = ['type' => 'string', 'format' => 'date-time'],
                'email' => $schema = ['type' => 'string', 'format' => 'email'],
                'url' => $schema = ['type' => 'string', 'format' => 'uri'],
                default => null,
            };
        }

        if (($schema['type'] ?? null) === 'array') {
            $schema['items'] = ['type' => 'string'];
        }

        return $schema;
    }

    /**
     * @param array<int, mixed> $attributes
     */
    private function schemaAttribute(array $attributes): ?SchemaAttribute
    {
        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    private function typeFromReflection(?\ReflectionType $type): string
    {
        if (! $type instanceof ReflectionNamedType) {
            return 'string';
        }

        return match ($type->getName()) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function containsRule(array $rules, string $expected): bool
    {
        foreach ($rules as $rule) {
            $ruleName = is_object($rule) ? $rule::class : strtolower(strtok((string) $rule, ':') ?: (string) $rule);

            if ($ruleName === $expected) {
                return true;
            }
        }

        return false;
    }
}
