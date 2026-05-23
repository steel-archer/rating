<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Api;

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
        ?string $loginAs,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'uri' => '/api/teams/suggest?q=test',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function ($client, array $objects) {
            },
        ];

        yield 'suggest returns matching teams' => [
            'uri' => '/api/teams/suggest?q=%D0%90%D0%BB%D1%8C%D1%84',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(1, $data);
                static::assertStringContainsString('Альфа', $data[0]['name']);
            },
        ];

        yield 'suggest returns empty for no match' => [
            'uri' => '/api/teams/suggest?q=xyz',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(0, $data);
            },
        ];

        yield 'suggest requires q parameter' => [
            'uri' => '/api/teams/suggest',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'expectedStatus' => 404,
            'afterCallback' => static function ($client, array $objects) {
            },
        ];
    }
}
