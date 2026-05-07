<?php

namespace Kadonix\Routebook\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Kadonix\Routebook\SpecGenerator;

final class ExportSpecCommand extends Command
{
    protected $signature = 'routebook:export {path=public/routebook.json : Where the generated document should be written} {--group= : Export only one Routebook group}';

    protected $description = 'Export the generated API documentation document to a JSON file.';

    public function handle(SpecGenerator $generator, Filesystem $files): int
    {
        $path = base_path($this->argument('path'));
        $directory = dirname($path);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        $files->put($path, json_encode($generator->generate($this->option('group') ?: null), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $this->info("Routebook document exported to {$path}");

        return self::SUCCESS;
    }
}
