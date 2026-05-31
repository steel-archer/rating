<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\TeamManagement;

use App\Classic\Entity\TeamPlayer;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateSquadControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/team_management.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testUpdateSquad(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'add player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_lesya']->getId()],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $entry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_lesya']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertNotNull($entry);
            },
        ];

        yield 'remove player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [$objects['player_franko']->getId()],
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $entry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_franko']->getId(),
                        'team' => $objects['team_alpha']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertNull($entry);
            },
        ];

        yield 'add and remove in one request' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_lesya']->getId()],
                    'removePlayerIds' => [$objects['player_franko']->getId()],
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $addedEntry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_lesya']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertNotNull($addedEntry);

                $removedEntry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_franko']->getId(),
                        'team' => $objects['team_alpha']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertNull($removedEntry);
            },
        ];

        yield 'cannot remove self' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [$objects['player_shevchenko']->getId()],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.cannot_remove_self');
            },
        ];

        yield 'player in another team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_kotsubynsky']->getId()],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertStringStartsWith('team_management.error.player_in_another_team:', $json['error']);
            },
        ];

        yield 'already in team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_franko']->getId()],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertStringStartsWith('team_management.error.already_in_team:', $json['error']);
            },
        ];

        yield 'denied for non-captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_member',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_lesya']->getId()],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.not_captain');
            },
        ];

        yield 'empty changes' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);
            },
        ];

        yield 'transfer captaincy' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [],
                    'newCaptainId' => $objects['player_franko']->getId(),
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $newCaptainEntry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_franko']->getId(),
                        'team' => $objects['team_alpha']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertTrue($newCaptainEntry->isCaptain());

                $oldCaptainEntry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_shevchenko']->getId(),
                        'team' => $objects['team_alpha']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertFalse($oldCaptainEntry->isCaptain());
            },
        ];

        yield 'transfer captaincy to removed player fails' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [$objects['player_franko']->getId()],
                    'newCaptainId' => $objects['player_franko']->getId(),
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.player_not_in_team');
            },
        ];

        yield 'transfer captaincy to newly added player' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_lesya']->getId()],
                    'removePlayerIds' => [],
                    'newCaptainId' => $objects['player_lesya']->getId(),
                ],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $newCaptainEntry = static::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayer::class)
                    ->findOneBy([
                        'player' => $objects['player_lesya']->getId(),
                        'season' => $objects['season_current']->getId(),
                    ]);
                static::assertTrue($newCaptainEntry->isCaptain());
            },
        ];

        yield 'player not found' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client) => self::post(
                $client,
                [
                    'addPlayerIds' => [999999],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.player_not_found');
            },
        ];

        yield 'remove player not in team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [$objects['player_lesya']->getId()],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.player_not_in_team');
            },
        ];

        yield 'max players exceeded' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/team_management.yaml',
                'Entity/team_management_full.yaml',
            ],
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [$objects['player_extra']->getId()],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.max_players');
            },
        ];

        yield 'invalid payload negative id' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client) => self::post(
                $client,
                [
                    'addPlayerIds' => [-1],
                    'removePlayerIds' => [],
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'transfer captaincy to self fails' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [],
                    'newCaptainId' => $objects['player_shevchenko']->getId(),
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.already_captain');
            },
        ];

        yield 'transfer captaincy to player not in squad' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                [
                    'addPlayerIds' => [],
                    'removePlayerIds' => [],
                    'newCaptainId' => $objects['player_lesya']->getId(),
                ],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.player_not_in_team');
            },
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function post(KernelBrowser $client, array $data): void
    {
        $client->request(
            'POST',
            '/my/team/update-squad',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    protected static function assertError(KernelBrowser $client, string $expectedError): void
    {
        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertSame($expectedError, $json['error']);
    }
}
