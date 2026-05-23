<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Api;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SessionTeamPlayersControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/squad.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessionTeamPlayers(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'returns base squad players' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/api/session/' . $objects['session_squad_approved']->getId() . '/team/' . $objects['team_alpha']->getId() . '/players',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertNotEmpty($data);
                $baseGroup = array_filter($data, static fn($item) => $item['group'] === 'base');
                static::assertNotEmpty($baseGroup);
            },
        ];

        yield 'returns season players for team with history' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/api/session/' . $objects['session_squad_approved']->getId() . '/team/' . $objects['team_beta']->getId() . '/players',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertNotEmpty($data);
                $seasonGroup = array_filter($data, static fn($item) => $item['group'] === 'season');
                static::assertNotEmpty($seasonGroup);
            },
        ];

        yield 'returns empty for team without squad' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/api/session/' . $objects['session_squad_approved']->getId() . '/team/' . $objects['team_gamma']->getId() . '/players',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertEmpty($data);
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_other',
            'uri' => static fn(array $objects) => '/api/session/' . $objects['session_squad_approved']->getId() . '/team/' . $objects['team_alpha']->getId() . '/players',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/api/session/' . $objects['session_squad_approved']->getId() . '/team/' . $objects['team_alpha']->getId() . '/players',
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
