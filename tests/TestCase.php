<?php

namespace Kadonix\Routebook\Tests;

use Kadonix\Routebook\RoutebookServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            RoutebookServiceProvider::class,
        ];
    }
}
