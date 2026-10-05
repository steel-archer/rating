<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim\Squad;

use App\Classic\Entity\Team;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Classic\Service\SessionResultService;
use App\Common\Entity\Player;
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
        ?callable $beforeRequest = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($beforeRequest !== null) {
            $beforeRequest($objects);
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
        yield 'draft distributed tournament rejects squad with a future deadline' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournament_draft_distributed.yaml'],
            'loginAs' => 'user_draft_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_draft_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function ($client, array $objects) {
                $doctrine = static::getContainer()->get('doctrine');
                $teams = $doctrine->getRepository(TournamentSessionTeam::class)->findAll();
                static::assertCount(1, $teams);
                static::assertSame($objects['session_team_draft']->getId(), $teams[0]->getId());
                static::assertSame(0, $doctrine->getRepository(TournamentSessionTeamPlayer::class)->count([]));
            },
        ];

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

        yield 'save squad with new player with town picked from list' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Ще команда 2',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    [
                        'id' => null,
                        'lastName' => 'Містенко',
                        'firstName' => 'Місто',
                        'patronymic' => 'Містович',
                        'townId' => $objects['town_kyiv']->getId(),
                        'countryId' => $objects['country_ukraine']->getId(),
                    ],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);

                $doctrine = static::getContainer()->get('doctrine');
                $player = $doctrine->getRepository(Player::class)
                    ->findOneBy(['lastName' => 'Містенко']);
                static::assertInstanceOf(Player::class, $player);
                static::assertSame('Київ', $player->getTown()?->getName());
            },
        ];

        yield 'save squad with new player with hand-typed town and country' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Ще команда 3',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    [
                        'id' => null,
                        'lastName' => 'Новомістенко',
                        'firstName' => 'Новий',
                        'patronymic' => null,
                        'townName' => 'Жмеринка',
                        'countryId' => $objects['country_ukraine']->getId(),
                    ],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);

                $doctrine = static::getContainer()->get('doctrine');
                $player = $doctrine->getRepository(Player::class)
                    ->findOneBy(['lastName' => 'Новомістенко']);
                static::assertInstanceOf(Player::class, $player);
                $town = $player->getTown();
                static::assertNotNull($town);
                static::assertSame('Жмеринка', $town->getName());
                static::assertSame('Україна', $town->getCountry()->getName());
            },
        ];

        yield 'error: new player with town but no country' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Команда без країни',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    [
                        'id' => null,
                        'lastName' => 'Безкраїнько',
                        'firstName' => 'Тест',
                        'patronymic' => null,
                        'townId' => $objects['town_kyiv']->getId(),
                    ],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringContainsString('country_required', $data['error']);
            },
        ];

        yield 'save squad with existing team and existing players' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'oneTimeName' => 'Зоряні Леви',
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

        yield 'save squad without a captain' => [
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
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);

                // The newly saved team has a player, none of whom is a captain.
                $doctrine = static::getContainer()->get('doctrine');
                $team = $doctrine->getRepository(Team::class)->findOneBy(['name' => 'Ще команда']);
                static::assertNotNull($team);

                $sessionTeam = $doctrine->getRepository(TournamentSessionTeam::class)
                    ->findOneBy(['team' => $team]);
                static::assertNotNull($sessionTeam);

                $savedPlayers = $doctrine->getRepository(TournamentSessionTeamPlayer::class)
                    ->findBy(['tournamentSessionTeam' => $sessionTeam]);
                static::assertCount(1, $savedPlayers);
                static::assertFalse($savedPlayers[0]->isCaptain());
            },
        ];

        yield 'error: former host who downloaded the package cannot be a player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Команда підозри',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
                    ['id' => $objects['player_kotsubynsky']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertStringStartsWith('squad.error.player_was_host_downloaded:', $data['error']);
                // The error carries the player name and the venue where they hosted.
                static::assertStringContainsString('Коцюбинський', $data['error']);
                static::assertStringContainsString('Квіз-бар Київ', $data['error']);
            },
        ];

        yield 'former host who did not download the package is allowed as a player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'payload' => static fn(array $objects) => [
                'teamName' => 'Команда Франка',
                'townId' => $objects['town_kyiv']->getId(),
                'players' => [
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
                'players' => array_fill(0, 10, ['id' => null, 'lastName' => 'Тест', 'firstName' => 'Тест', 'patronymic' => null, 'townId' => null]),
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

        yield 'save squad invalidates cached session results' => [
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
            'afterCallback' => static function ($client, array $objects) {
                $em = static::getContainer()->get('doctrine')->getManager();
                $session = $em->find(TournamentSession::class, $objects['session_squad_approved']->getId());

                $results = static::getContainer()->get(SessionResultService::class)->getSessionResults($session);
                static::assertCount(2, $results);

                $playerNames = array_merge(
                    ...array_map(
                        static fn($team) => array_map(static fn($player) => $player->playerName, $team->players),
                        $results,
                    ),
                );
                static::assertContains('Тестенко Тест', $playerNames);
            },
            'beforeRequest' => static function (array $objects) {
                $results = static::getContainer()
                    ->get(SessionResultService::class)
                    ->getSessionResults($objects['session_squad_approved']);

                static::assertCount(1, $results, 'Precondition: session should have exactly one team before the new squad is saved.');
            },
        ];
    }
}
