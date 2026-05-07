<?php

namespace Kadonix\Routebook\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Kadonix\Routebook\SpecGenerator;

final class CoverageCommand extends Command
{
    protected $signature = 'routebook:coverage';

    protected $description = 'Show API documentation coverage for registered routes.';

    public function handle(Router $router, SpecGenerator $generator): int
    {
        $documented = $this->documentedOperations($generator->generate());
        $routes = $this->candidateRoutes($router);
        $total = count($routes);
        $covered = 0;

        foreach ($routes as $route) {
            $covered += isset($documented[$route]) ? 1 : 0;
        }

        $percent = $total === 0 ? 100 : round(($covered / $total) * 100, 2);

        $this->line("Routebook coverage: {$percent}%");
        $this->line("{$covered} documented / {$total} API routes");

        return $covered === $total ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function candidateRoutes(Router $router): array
    {
        $routes = [];

        foreach ($router->getRoutes() as $route) {
            if (! $this->shouldInspectRoute($route)) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $routes[] = strtolower($method) . ' ' . '/' . ltrim($route->uri(), '/');
            }
        }

        return $routes;
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
}
