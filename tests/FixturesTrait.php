<?php

declare(strict_types=1);

namespace App\Tests;

use Fidry\AliceDataFixtures\Persistence\PurgeMode;

trait FixturesTrait
{
    /**
     * @param list<string> $fixtures Fixture file paths relative to tests/Fixtures/
     * @return array<string, object>
     */
    protected static function loadFixtures(array $fixtures): array
    {
        $basePath = dirname(__DIR__) . '/tests/Fixtures/';
        $files = array_map(static fn(string $f) => $basePath . $f, $fixtures);

        return static::getContainer()
            ->get('fidry_alice_data_fixtures.loader.doctrine')
            ->load($files, [], [], PurgeMode::createDeleteMode());
    }
}
