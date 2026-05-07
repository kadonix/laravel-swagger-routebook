<?php

namespace Kadonix\Routebook\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Kadonix\Routebook\SpecGenerator;

final class CheckDocsCommand extends Command
{
    protected $signature = 'routebook:check';

    protected $description = 'Audit Routebook documentation and report missing or weak endpoint docs.';

    public function handle(Router $router, SpecGenerator $generator): int
    {
        $document = $generator->generate();
        $documented = $this->documentedOperations($document);
        $issues = [];

        foreach ($router->getRoutes() as $route) {
            if (! $this->shouldInspectRoute($route)) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $key = strtolower($method) . ' ' . '/' . ltrim($route->uri(), '/');

                if (! isset($documented[$key])) {
                    $issues[] = ['Missing docs', $key];
                }
            }
        }

        foreach (($document['paths'] ?? []) as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (($operation['summary'] ?? '') === '') {
                    $issues[] = ['Missing summary', "{$method} {$path}"];
                }

                if (($operation['responses'] ?? []) === []) {
                    $issues[] = ['Missing responses', "{$method} {$path}"];
                }

                foreach ($this->pathParameters($path) as $parameter) {
                    if (! $this->hasPathParameter($operation, $parameter)) {
                        $issues[] = ['Missing path parameter', "{$method} {$path} {{$parameter}}"];
                    }
                }
            }
        }

        if ($issues === []) {
            $this->info('Routebook check passed. No documentation issues found.');

            return self::SUCCESS;
        }

        $this->table(['Issue', 'Target'], $issues);

        return self::FAILURE;
    }

    private function shouldInspectRoute(\Illuminate\Routing\Route $route): bool
    {
        if (str_starts_with($route->uri(), trim((string) config('routebook.routes.prefix', 'docs'), '/'))) {
            return false;
        }

        $action = $route->getAction('uses');

        return is_array($action) || (is_string($action) && ! str_contains($action, 'Closure'));
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, bool>
     */
    private function documentedOperations(array $document): array
    {
        $operations = [];

        foreach ($document['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $_operation) {
                $operations[$method . ' ' . $path] = true;
            }
        }

        return $operations;
    }

    /**
     * @return array<int, string>
     */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)}/', $path, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function hasPathParameter(array $operation, string $name): bool
    {
        foreach ($operation['parameters'] ?? [] as $parameter) {
            if (($parameter['in'] ?? null) === 'path' && ($parameter['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
}
