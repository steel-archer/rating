<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Sentry;

use App\Common\Sentry\SentryEventSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sentry\Event;

class SentryEventSanitizerTest extends TestCase
{
    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $expected
     */
    #[DataProvider('requestProvider')]
    public function testRequestIsSanitized(array $request, array $expected): void
    {
        $event = Event::createEvent();
        $event->setRequest($request);

        $result = (new SentryEventSanitizer())($event);

        $this->assertSame($expected, $result->getRequest());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function requestProvider(): iterable
    {
        yield 'cookies are dropped entirely' => [
            [
                'url' => 'https://rating.example.org/my/contacts',
                'cookies' => ['PHPSESSID' => 'abc', 'REMEMBERME' => 'secret'],
            ],
            [
                'url' => 'https://rating.example.org/my/contacts',
            ],
        ];

        yield 'query string is dropped' => [
            [
                'url' => 'https://rating.example.org/search',
                'query_string' => 'token=abc123&page=2',
            ],
            [
                'url' => 'https://rating.example.org/search',
            ],
        ];

        yield 'auth headers are masked, others kept' => [
            [
                'headers' => [
                    'Authorization' => ['Bearer secret-token'],
                    'Cookie' => ['PHPSESSID=abc'],
                    'X-CSRF-Token' => ['csrf-value'],
                    'Content-Type' => ['application/json'],
                ],
            ],
            [
                'headers' => [
                    'Authorization' => '[Filtered]',
                    'Cookie' => '[Filtered]',
                    'X-CSRF-Token' => '[Filtered]',
                    'Content-Type' => ['application/json'],
                ],
            ],
        ];

        yield 'header matching is case-insensitive' => [
            [
                'headers' => [
                    'authorization' => ['Bearer x'],
                    'COOKIE' => ['a=b'],
                ],
            ],
            [
                'headers' => [
                    'authorization' => '[Filtered]',
                    'COOKIE' => '[Filtered]',
                ],
            ],
        ];

        yield 'sensitive body fields are masked, others kept' => [
            [
                'data' => [
                    'password' => 'hunter2',
                    'access_token' => 'tok-123',
                    'email' => 'user@example.org',
                    'phone' => '+380991234567',
                    'telegram' => '@user',
                    'facebook' => 'fb.com/user',
                    'displayName' => 'Іван',
                ],
            ],
            [
                'data' => [
                    'password' => '[Filtered]',
                    'access_token' => '[Filtered]',
                    'email' => '[Filtered]',
                    'phone' => '[Filtered]',
                    'telegram' => '[Filtered]',
                    'facebook' => '[Filtered]',
                    'displayName' => 'Іван',
                ],
            ],
        ];

        yield 'nested body fields are masked recursively' => [
            [
                'data' => [
                    'contact' => [
                        'phone' => '+380991234567',
                        'city' => 'Kyiv',
                    ],
                    'meta' => ['note' => 'plain'],
                ],
            ],
            [
                'data' => [
                    'contact' => [
                        'phone' => '[Filtered]',
                        'city' => 'Kyiv',
                    ],
                    'meta' => ['note' => 'plain'],
                ],
            ],
        ];

        yield 'body field matching is case-insensitive and substring-based' => [
            [
                'data' => [
                    'userPassword' => 'x',
                    'API_KEY' => 'y',
                    'title' => 'kept',
                ],
            ],
            [
                'data' => [
                    'userPassword' => '[Filtered]',
                    'API_KEY' => '[Filtered]',
                    'title' => 'kept',
                ],
            ],
        ];

        yield 'non-array body is left untouched' => [
            [
                'data' => 'raw body string',
            ],
            [
                'data' => 'raw body string',
            ],
        ];

        yield 'request without sensitive parts is unchanged' => [
            [
                'url' => 'https://rating.example.org/',
                'method' => 'GET',
            ],
            [
                'url' => 'https://rating.example.org/',
                'method' => 'GET',
            ],
        ];
    }

    public function testEmptyRequestIsNotTouched(): void
    {
        $event = Event::createEvent();

        $result = (new SentryEventSanitizer())($event);

        $this->assertSame([], $result->getRequest());
    }
}
