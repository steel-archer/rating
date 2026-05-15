<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SquadSaveControllerTest extends WebTestCase
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
    public function testSaveSquad(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        callable $payload,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request(
            'POST',
            $uri($objects),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload($objects), JSON_THROW_ON_ERROR),
        );

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'save squad with new team and new player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Нова команда',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'save squad with existing team and existing players' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => [
                    ['id' => $objects['player_shevchenko']->getId()],
                    ['id' => $objects['player_franko']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'error: team already in tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Новенко', 'firstName' => 'Новий', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('team_already_added', $data['error']);
            },
        ];

        yield 'error: player already played in tournament' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_gamma']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('player_already_played', $data['error']);
            },
        ];

        yield 'error: no captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Ще команда',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => null,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('captain_required', $data['error']);
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Тест',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'access denied for future session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_future']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Тест',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'error: empty team name' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => '',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('team_required', $data['error']);
            },
        ];

        yield 'error: new team without town' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Нова команда',
                'townId' => null,
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('town_required', $data['error']);
            },
        ];

        yield 'error: no players (DTO validation)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => [],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'error: too many players (DTO validation)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => array_fill(0, 9, ['id' => null, 'lastName' => 'Тест', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null]),
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'error: duplicate players' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => [
                    ['id' => $objects['player_shevchenko']->getId()],
                    ['id' => $objects['player_shevchenko']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('duplicate_players', $data['error']);
            },
        ];

        yield 'error: new player without last name' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => '', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('player_last_name_required', $data['error']);
            },
        ];

        yield 'error: new player without first name' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'players' => [
                    ['id' => null, 'lastName' => 'Тестенко', 'firstName' => '', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('player_first_name_required', $data['error']);
            },
        ];
    }
}
