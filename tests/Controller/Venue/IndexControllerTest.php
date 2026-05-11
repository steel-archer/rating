<?php

declare(strict_types=1);

namespace App\Tests\Controller\Venue;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testIndex(
        string $method,
        string $uri,
        ?string $loginAs,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures(['Entity/base.yaml', 'Entity/users.yaml']);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request($method, $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'method' => 'GET',
            'uri' => '/venues',
            'loginAs' => null,
            'expectedStatus' => 302,
        ];

        yield 'venues page returns 200' => [
            'method' => 'GET',
            'uri' => '/venues',
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
        ];

        yield 'venues page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/venues',
            'loginAs' => 'user_with_player',
            'expectedStatus' => 405,
        ];
    }
}
