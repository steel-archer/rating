<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\TeamManagement;

use App\Entity\TeamPlayer;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LeaveControllerTest extends WebTestCase
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
    public function testLeave(
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

        $client->request(
            'POST',
            '/my/team/leave',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

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
            'loginAs' => 'user_member',
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

        yield 'denied for captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('team_management.error.captain_cannot_leave', $json['error']);
            },
        ];

        yield 'denied for outsider' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_outsider',
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('team_management.error.player_not_in_team', $json['error']);
            },
        ];
    }
}
