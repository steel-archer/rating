<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\TeamManagement;

use App\Entity\Team;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateTeamControllerTest extends WebTestCase
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
    public function testUpdateTeam(
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
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => 'Нова назва',
                'townId' => $objects['town_lviv']->getId(),
            ]),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $team = static::getContainer()->get('doctrine')
                    ->getRepository(Team::class)
                    ->find($objects['team_alpha']->getId());
                static::assertSame('Нова назва', $team->getName());
                static::assertSame($objects['town_lviv']->getId(), $team->getTown()->getId());
            },
        ];

        yield 'same name same town allowed (no change)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => 'Альфа',
                'townId' => $objects['town_kyiv']->getId(),
            ]),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'name taken in same town' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => 'Гамма',
                'townId' => $objects['town_kyiv']->getId(),
            ]),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.name_taken');
            },
        ];

        yield 'same name different town allowed' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => 'Гамма',
                'townId' => $objects['town_lviv']->getId(),
            ]),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'town not found' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client) => self::post($client, [
                'name' => 'Тест',
                'townId' => 999999,
            ]),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertError($client, 'team_management.error.town_not_found');
            },
        ];

        yield 'empty name' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => '',
                'townId' => $objects['town_kyiv']->getId(),
            ]),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'denied for non-captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_member',
            'action' => static fn(KernelBrowser $client, array $objects) => self::post($client, [
                'name' => 'Hack',
                'townId' => $objects['town_kyiv']->getId(),
            ]),
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
            '/my/team/update',
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
