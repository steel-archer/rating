<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

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

    public static function dataProvider(): iterable
    {
        yield 'tournaments page returns 200' => [
            'method' => 'GET',
            'uri' => '/tournaments',
            'expectedStatus' => 200,
        ];

        yield 'tournaments page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/tournaments',
            'expectedStatus' => 405,
        ];
    }
}
