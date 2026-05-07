<?php

namespace Kadonix\Routebook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Kadonix\Routebook\Export\PostmanExporter;
use Kadonix\Routebook\SpecGenerator;

final class ExportPostmanCommand extends Command
{
    protected $signature = 'routebook:export-postman {path=public/routebook.postman.json : Where the Postman collection should be written} {--group= : Export only one Routebook group}';

    protected $description = 'Export the Routebook document as a Postman collection.';

    public function handle(SpecGenerator $generator, PostmanExporter $exporter, Filesystem $files): int
    {
        $document = $generator->generate($this->option('group') ?: null);
        $collection = $exporter->collection($document);

        $path = base_path($this->argument('path'));
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        $this->info("Postman collection exported to {$path}");

        return self::SUCCESS;
    }
}
