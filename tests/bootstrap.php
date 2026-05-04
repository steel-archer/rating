<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    $appEnv = $_SERVER['APP_ENV'] ?? null;
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
    if ($appEnv !== null) {
        $_SERVER['APP_ENV'] = $appEnv;
        $_ENV['APP_ENV'] = $appEnv;
    }
}

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

passthru('php bin/console doctrine:migrations:migrate --no-interaction --env=test 2>&1');
