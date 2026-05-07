<?php

namespace Kadonix\Routebook;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kadonix\Routebook\Annotations\DocblockParser;
use Kadonix\Routebook\Commands\CheckDocsCommand;
use Kadonix\Routebook\Commands\CoverageCommand;
use Kadonix\Routebook\Commands\ExportPostmanCommand;
use Kadonix\Routebook\Commands\ExportSpecCommand;
use Kadonix\Routebook\Export\PostmanExporter;
use Kadonix\Routebook\Schema\SchemaFactory;

final class RoutebookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/routebook.php', 'routebook');

        $this->app->singleton(DocblockParser::class);
        $this->app->singleton(SchemaFactory::class);
        $this->app->singleton(SpecGenerator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'routebook');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/routebook.php' => config_path('routebook.php'),
            ], 'routebook-config');

            $this->commands([
                CheckDocsCommand::class,
                CoverageCommand::class,
                ExportPostmanCommand::class,
                ExportSpecCommand::class,
            ]);
        }

        if ((bool) config('routebook.routes.enabled', true)) {
            $this->registerRoutes();
        }
    }

    private function registerRoutes(): void
    {
        $prefix = trim((string) config('routebook.routes.prefix', 'docs'), '/');
        $json = trim((string) config('routebook.routes.json', 'spec.json'), '/');
        $middleware = config('routebook.routes.middleware', ['web']);

        Route::middleware($middleware)->prefix($prefix)->group(function () use ($json): void {
            Route::get('/', static fn () => view('routebook::index'))->name('routebook.ui');

            Route::get($json, static fn (SpecGenerator $generator): JsonResponse => response()->json($generator->generate(request('group'))))
                ->name('routebook.json');

            Route::get('export/postman', static function (SpecGenerator $generator, PostmanExporter $exporter): JsonResponse {
                return response()->json($exporter->collection($generator->generate(request('group'))))
                    ->withHeaders(['Content-Disposition' => 'attachment; filename="routebook.postman.json"']);
            })->name('routebook.export.postman');

        });
    }
}
