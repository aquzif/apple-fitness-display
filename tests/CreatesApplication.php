<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $basePath = __DIR__ . '/../';
        $environmentFile = $basePath . '.env';
        $createdEnvironmentFile = false;

        if (! file_exists($environmentFile)) {
            file_put_contents($environmentFile, '');
            $createdEnvironmentFile = true;
        }

        $app = require $basePath . 'bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        if ($createdEnvironmentFile) {
            register_shutdown_function(static fn () => @unlink($environmentFile));
        }

        return $app;
    }
}
