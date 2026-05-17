<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim\Squad;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SquadControllerTest extends WebTestCase
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
    public function testSquad(
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
        yield 'squad page for approved session with past date' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'expectedStatus' => 200,
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'expectedStatus' => 403,
        ];

        yield 'access denied for future date' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_future']->getId() . '/squad',
            'expectedStatus' => 403,
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_squad_approved']->getId() . '/squad',
            'expectedStatus' => 302,
        ];
    }
}
