<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Player;

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
            'uri' => '/players',
            'loginAs' => null,
            'expectedStatus' => 302,
        ];

        yield 'players page returns 200' => [
            'method' => 'GET',
            'uri' => '/players',
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
        ];

        yield 'players page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/players',
            'loginAs' => 'user_with_player',
            'expectedStatus' => 405,
        ];
    }
}
