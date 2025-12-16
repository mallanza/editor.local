<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        // Prevent local optimization artifacts from breaking the test environment.
        // If config/routes are cached, Laravel can ignore phpunit.xml env vars (e.g. APP_ENV=testing),
        // which leads to auth/CSRF failures (419) and stale route definitions.
        foreach (['config.php', 'routes-v7.php', 'events.php'] as $cacheFile) {
            $path = __DIR__ . '/../bootstrap/cache/' . $cacheFile;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
