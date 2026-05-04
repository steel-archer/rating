<?php

declare(strict_types=1);

namespace App\Tests\Controller\Team;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    #[DataProvider('dataProvider')]
    public function testIndex(
        string $method,
        string $uri,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $client->request($method, $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'teams page returns 200' => [
            'method' => 'GET',
            'uri' => '/teams',
            'expectedStatus' => 200,
        ];

        yield 'teams page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/teams',
            'expectedStatus' => 405,
        ];
    }
}
