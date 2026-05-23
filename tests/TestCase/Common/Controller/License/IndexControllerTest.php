<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\License;

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

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'license page returns 200' => [
            'method' => 'GET',
            'uri' => '/license',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertSelectorExists('.license-text');
                static::assertSelectorTextContains('h1', 'Ліцензія');
                static::assertSelectorTextContains('.license-text', 'Богдан Сокульський');
                static::assertSelectorTextContains('.license-text', 'Комерційне використання');
                static::assertSelectorTextContains('.license-text', 'Етична умова');
            },
        ];

        yield 'license page is accessible without authentication' => [
            'method' => 'GET',
            'uri' => '/license',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
                static::assertResponseHeaderSame('X-Frame-Options', 'DENY');
            },
        ];

        yield 'license page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/license',
            'expectedStatus' => 405,
            'afterCallback' => static function ($client) {
            },
        ];
    }
}
