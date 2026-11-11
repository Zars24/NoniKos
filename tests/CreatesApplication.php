<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        // A cached config file bypasses phpunit.xml entirely, so RefreshDatabase
        // would migrate and wipe the real database. Refuse to start instead.
        if (file_exists(__DIR__.'/../bootstrap/cache/config.php')) {
            throw new RuntimeException(
                'Configuration is cached; tests would run against the real database. '
                .'Run `php artisan optimize:clear` before `php artisan test`.'
            );
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
