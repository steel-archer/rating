<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TeamSuggestControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSuggest(
        string $uri,
        array $fixtures,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        self::loadFixtures($fixtures);

        $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'suggest returns matching teams' => [
            'uri' => '/api/teams/suggest?q=%D0%90%D0%BB%D1%8C%D1%84',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertCount(1, $data);
                static::assertStringContainsString('Альфа', $data[0]['name']);
            },
        ];

        yield 'suggest returns empty for no match' => [
            'uri' => '/api/teams/suggest?q=xyz',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertCount(0, $data);
            },
        ];

        yield 'suggest requires q parameter' => [
            'uri' => '/api/teams/suggest',
            'fixtures' => [],
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];
    }
}
