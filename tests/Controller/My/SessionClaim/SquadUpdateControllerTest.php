<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim;

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
    }
}
