<?php

namespace Kadonix\Routebook;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Kadonix\Routebook\Annotations\DocblockParser;
use Kadonix\Routebook\Attributes\Body;
use Kadonix\Routebook\Attributes\Endpoint;
use Kadonix\Routebook\Attributes\Example;
use Kadonix\Routebook\Attributes\Parameter;
use Kadonix\Routebook\Attributes\RequestBody;
use Kadonix\Routebook\Attributes\Response;
use Kadonix\Routebook\Attributes\Returns;
use Kadonix\Routebook\Schema\SchemaFactory;
use ReflectionMethod;

final class SpecGenerator
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $componentSchemas = [];

    public function __construct(
        private readonly Router $router,
        private readonly Config $config,
        private readonly SchemaFactory $schemas,
        private readonly DocblockParser $docblocks,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generate(?string $group = null): array
    {
        $paths = [];
        $usesAuth = false;
        $this->componentSchemas = [];

        foreach ($this->router->getRoutes() as $route) {
            $operation = $this->operationForRoute($route, $group);

            if ($operation === null) {
                continue;
            }

            [$method, $path, $payload] = $operation;
            $usesAuth = $usesAuth || array_key_exists('security', $payload);
            $paths[$path][$method] = $payload;
        }

        ksort($paths);

        return array_filter([
            'openapi' => '3.1.0',
            'info' => array_filter([
                'title' => $this->config->get('routebook.title', 'Laravel API'),
                'version' => $this->config->get('routebook.version', '1.0.0'),
                'description' => $this->config->get('routebook.description'),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'servers' => $this->config->get('routebook.servers', []),
            'paths' => $paths,
            'components' => $this->components($usesAuth),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    private function components(bool $usesAuth): array
    {
        return array_filter([
            'securitySchemes' => $usesAuth ? [
                $this->authSchemeName() => $this->authScheme(),
            ] : null,
            'schemas' => $this->componentSchemas,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, mixed>}|null
     */
    private function operationForRoute(Route $route, ?string $group = null): ?array
    {
        $reflection = $this->reflectionForRoute($route);
        $parsed = $reflection ? $this->docblocks->parse($reflection) : [];
        $endpoint = $reflection ? $this->firstAttribute($reflection, Endpoint::class) ?? ($parsed['endpoint'] ?? null) : null;
        $includeUnannotated = (bool) $this->config->get('routebook.scan.include_unannotated_routes', false);

        if ($endpoint === null && ! $includeUnannotated) {
            return null;
        }

        if ($group !== null && $group !== '' && ($endpoint?->group ?? 'default') !== $group) {
            return null;
        }

        $httpMethods = array_values(array_diff($route->methods(), ['HEAD']));
        $method = strtolower($endpoint?->method ?? $httpMethods[0] ?? 'get');
        $path = $this->normalizePath($endpoint?->path ?? $route->uri());

        $security = $this->securityForRoute($route, $endpoint);

        $payload = array_filter([
            'summary' => $endpoint?->summary ?? $route->getName(),
            'description' => $endpoint?->description,
            'operationId' => $endpoint?->operationId ?? $this->operationId($route, $method),
            'tags' => $endpoint?->tags,
            'x-routebook-group' => $endpoint?->group,
            'deprecated' => $endpoint?->deprecated ?: null,
            'parameters' => $reflection ? $this->parameters($reflection, $route) : $this->pathParameters($route),
            'requestBody' => $reflection ? $this->requestBody($reflection) : null,
            'responses' => $reflection ? $this->responses($reflection) : ['200' => ['description' => 'OK']],
            'security' => $security,
        ], static fn (mixed $value): bool => $value !== null && $value !== [] && $value !== false);

        return [$method, $path, $payload];
    }

    /**
     * @return array<int, array<string, array<int, string>>>
     */
    private function securityForRoute(Route $route, ?Endpoint $endpoint): array
    {
        if ($endpoint?->security !== []) {
            return $endpoint->security;
        }

        $default = $this->config->get('routebook.scan.default_security', []);

        if ($default !== []) {
            return $default;
        }

        if ((bool) $this->config->get('routebook.auth.enabled', true) && ($endpoint?->auth || $this->routeUsesAuthMiddleware($route))) {
            return [
                [$this->authSchemeName() => []],
            ];
        }

        return [];
    }

    private function routeUsesAuthMiddleware(Route $route): bool
    {
        if (! (bool) $this->config->get('routebook.scan.detect_auth_middleware', true)) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:'))) {
                return true;
            }
        }

        return false;
    }

    private function authSchemeName(): string
    {
        return (string) $this->config->get('routebook.auth.scheme', 'bearerAuth');
    }

    /**
     * @return array<string, string>
     */
    private function authScheme(): array
    {
        return array_filter([
            'type' => $this->config->get('routebook.auth.type', 'http'),
            'scheme' => $this->config->get('routebook.auth.scheme_name', 'bearer'),
            'bearerFormat' => $this->config->get('routebook.auth.bearer_format', 'JWT'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function reflectionForRoute(Route $route): ?ReflectionMethod
    {
        $action = $route->getAction('uses');

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);

            return method_exists($class, $method) ? new ReflectionMethod($class, $method) : null;
        }

        if (is_array($action) && isset($action[0], $action[1]) && method_exists($action[0], $action[1])) {
            return new ReflectionMethod($action[0], $action[1]);
        }

        if (is_string($action) && class_exists($action) && method_exists($action, '__invoke')) {
            return new ReflectionMethod($action, '__invoke');
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parameters(ReflectionMethod $method, Route $route): array
    {
        $parameters = $this->pathParameters($route);

        foreach ($this->docblocks->parse($method)['parameters'] as $parameter) {
            $parameters[] = $this->parameterPayload($parameter);
        }

        foreach ($method->getAttributes(Parameter::class) as $attribute) {
            /** @var Parameter $parameter */
            $parameter = $attribute->newInstance();
            $parameters[] = $this->parameterPayload($parameter);
        }

        return $parameters;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pathParameters(Route $route): array
    {
        return array_map(static fn (string $name): array => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ], $route->parameterNames());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestBody(ReflectionMethod $method): ?array
    {
        $parsed = $this->docblocks->parse($method);
        $body = $this->firstAttribute($method, RequestBody::class)
            ?? $this->firstAttribute($method, Body::class)
            ?? $parsed['requestBody'];

        if ($body === null) {
            return null;
        }

        return array_filter([
            'description' => $body->description,
            'required' => $body->required,
            'content' => [
                $body->contentType => [
                    ...array_filter([
                        'schema' => $this->schemaFor($body->schema, $body->from),
                        'examples' => $this->examples($method, 'request'),
                    ], static fn (mixed $value): bool => $value !== []),
                ],
            ],
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function responses(ReflectionMethod $method): array
    {
        $responses = [];

        foreach ($this->docblocks->parse($method)['responses'] as $response) {
            $responses[(string) $response->status] = $this->responsePayload($response, $this->examples($method, 'response', $response->status));
        }

        foreach ($method->getAttributes(Response::class) as $attribute) {
            /** @var Response $response */
            $response = $attribute->newInstance();
            $responses[(string) $response->status] = $this->responsePayload($response, $this->examples($method, 'response', $response->status));
        }

        foreach ($method->getAttributes(Returns::class) as $attribute) {
            /** @var Response $response */
            $response = $attribute->newInstance();
            $responses[(string) $response->status] = $this->responsePayload($response, $this->examples($method, 'response', $response->status));
        }

        return $responses ?: ['200' => ['description' => 'OK']];
    }

    /**
     * @return array<string, mixed>
     */
    private function parameterPayload(Parameter $parameter): array
    {
        return array_filter([
            'name' => $parameter->name,
            'in' => $parameter->in,
            'description' => $parameter->description,
            'required' => $parameter->required || $parameter->in === 'path',
            'schema' => ['type' => $parameter->type],
            'example' => $parameter->example,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(Response $response, array $examples = []): array
    {
        $payload = [
            'description' => $response->description,
        ];

        if ($response->schema !== null || $response->from !== null) {
            $schema = $this->schemaFor($response->schema, $response->from);

            if ($response->collection) {
                $schema = ['type' => 'array', 'items' => $schema];
            }

            $payload['content'] = [
                $response->contentType => [
                    ...array_filter([
                        'schema' => $schema,
                        'examples' => $examples,
                    ], static fn (mixed $value): bool => $value !== []),
                ],
            ];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|null $schema
     * @param class-string|null $from
     * @return array<string, mixed>
     */
    private function schemaFor(?array $schema = null, ?string $from = null): array
    {
        if ($schema !== null || $from === null || ! class_exists($from)) {
            return $this->schemas->make($schema, $from);
        }

        $name = $this->schemas->nameFor($from);
        $this->componentSchemas[$name] = $this->schemas->make(null, $from);

        return ['$ref' => '#/components/schemas/' . $name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function examples(ReflectionMethod $method, string $type, int|string|null $status = null): array
    {
        $examples = [];

        foreach ($this->docblocks->parse($method)['examples'] as $example) {
            if ($example->type === $type && ($status === null || (string) $example->status === (string) $status)) {
                $examples[$example->name] = ['value' => $example->value];
            }
        }

        foreach ($method->getAttributes(Example::class) as $attribute) {
            /** @var Example $example */
            $example = $attribute->newInstance();

            if ($example->type === $type && ($status === null || (string) $example->status === (string) $status)) {
                $examples[$example->name] = ['value' => $example->value];
            }
        }

        return $examples;
    }


    /**
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    private function firstAttribute(ReflectionMethod $method, string $attributeClass): ?object
    {
        $attributes = $method->getAttributes($attributeClass);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    private function normalizePath(string $uri): string
    {
        $path = '/' . ltrim($uri, '/');

        return preg_replace('/\{([^}:]+)[^}]*}/', '{$1}', $path) ?: $path;
    }

    private function operationId(Route $route, string $method): string
    {
        if ($route->getName()) {
            return str_replace(['.', '-'], '_', $route->getName());
        }

        return $method . '_' . str_replace(['/', '{', '}'], ['_', '', ''], $route->uri());
    }
}
