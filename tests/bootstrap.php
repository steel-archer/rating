<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

passthru('php bin/console doctrine:migrations:migrate --no-interaction --env=test 2>&1');
