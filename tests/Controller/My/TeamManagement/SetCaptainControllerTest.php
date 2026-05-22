<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\TeamManagement;

use App\Entity\TeamPlayer;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SetCaptainControllerTest extends WebTestCase
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
    public function testSetCaptain(
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
        yield 'success' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                ['playerId' => $objects['player_franko']->getId()],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $repo = static::getContainer()->get('doctrine')->getRepository(TeamPlayer::class);

                $oldCaptain = $repo->findOneBy([
                    'player' => $objects['player_shevchenko']->getId(),
                    'team' => $objects['team_alpha']->getId(),
                    'season' => $objects['season_current']->getId(),
                ]);
                static::assertFalse($oldCaptain->isCaptain());

                $newCaptain = $repo->findOneBy([
                    'player' => $objects['player_franko']->getId(),
                    'team' => $objects['team_alpha']->getId(),
                    'season' => $objects['season_current']->getId(),
                ]);
                static::assertTrue($newCaptain->isCaptain());
            },
        ];

        yield 'player not in team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                ['playerId' => $objects['player_lesya']->getId()],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.player_not_in_team');
            },
        ];

        yield 'denied for non-captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_member',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post(
                $client,
                ['playerId' => $objects['player_shevchenko']->getId()],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.not_captain');
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
            '/my/team/set-captain',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    private static function assertError(KernelBrowser $client, string $expectedError): void
    {
        $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertSame($expectedError, $json['error']);
    }
}
