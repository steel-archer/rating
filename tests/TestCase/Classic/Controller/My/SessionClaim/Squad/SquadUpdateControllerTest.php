<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim\Squad;

use App\Classic\Entity\TournamentSession;
use App\Classic\Service\SessionResultService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SquadUpdateControllerTest extends WebTestCase
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
    public function testUpdateSquad(
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
        yield 'update squad successfully (change captain)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'update squad: add new player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                    ['id' => null, 'lastName' => 'Новенко', 'firstName' => 'Новий', 'patronymic' => null, 'townId' => null],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'update squad: replace player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
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
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'error: duplicate players' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_lesya']->getId()],
                    ['id' => $objects['player_lesya']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('squad.error.duplicate_players', $data['error']);
            },
        ];

        yield 'update squad invalidates cached session results' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/update',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'players' => [
                    ['id' => $objects['player_franko']->getId()],
                ],
                'captainIndex' => 0,
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $em = static::getContainer()->get('doctrine')->getManager();
                $session = $em->find(TournamentSession::class, $objects['session_squad_approved']->getId());

                $results = static::getContainer()->get(SessionResultService::class)->getSessionResults($session);
                static::assertCount(1, $results);

                $playerNames = array_map(static fn($player) => $player->playerName, $results[0]->players);
                static::assertContains('Франко Іван Якович', $playerNames);
                static::assertNotContains('Українка Леся', $playerNames);
            },
            'beforeRequest' => static function (array $objects) {
                $results = static::getContainer()
                    ->get(SessionResultService::class)
                    ->getSessionResults($objects['session_squad_approved']);

                static::assertCount(1, $results);

                $playerNames = array_map(static fn($player) => $player->playerName, $results[0]->players);
                static::assertContains(
                    'Українка Леся',
                    $playerNames,
                    'Precondition: cached results should still show the original squad before the update.',
                );
            },
        ];
    }
}
