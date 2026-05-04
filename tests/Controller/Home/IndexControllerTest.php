<?php

declare(strict_types=1);

namespace App\Tests\Controller\Home;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    #[DataProvider('dataProvider')]
    public function testIndex(
        string $method,
        string $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $client->request($method, $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    public static function dataProvider(): iterable
    {
        yield 'home page returns 200' => [
            'method' => 'GET',
            'uri' => '/',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertSelectorExists('body');

                // security headers
                static::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
                static::assertResponseHeaderSame('X-Frame-Options', 'DENY');
                static::assertResponseHeaderSame('Referrer-Policy', 'strict-origin-when-cross-origin');
            },
        ];

        yield 'home page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/',
            'expectedStatus' => 405,
            'afterCallback' => static function ($client) {},
        ];
    }
}
