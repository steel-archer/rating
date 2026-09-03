<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Privacy;

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
        yield 'privacy page returns 200' => [
            'method' => 'GET',
            'uri' => '/privacy',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertSelectorExists('.content-page');
                static::assertSelectorTextContains('h1', 'Політика конфіденційності');
                static::assertSelectorTextContains('.content-page', 'Які дані ми збираємо');
                static::assertSelectorTextContains('.content-page', 'Дані, отримані через Google');
                static::assertSelectorTextContains('.content-page', 'Ваші права');
            },
        ];

        yield 'privacy page is accessible without authentication' => [
            'method' => 'GET',
            'uri' => '/privacy',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
                static::assertResponseHeaderSame('X-Frame-Options', 'DENY');
            },
        ];

        yield 'privacy page POST not allowed' => [
            'method' => 'POST',
            'uri' => '/privacy',
            'expectedStatus' => 405,
            'afterCallback' => static function ($client) {
            },
        ];
    }
}
