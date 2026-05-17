<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim\Squad;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SquadEditControllerTest extends WebTestCase
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
    public function testEditSquad(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'edit squad page for owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/edit',
            'expectedStatus' => 200,
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_other',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/edit',
            'expectedStatus' => 403,
        ];
    }
}
