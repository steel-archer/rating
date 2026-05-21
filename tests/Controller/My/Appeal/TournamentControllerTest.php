<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\Appeal;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TournamentControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/appeals.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testTournament(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'jury member sees appeals with actions' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/my/appeals/' . $objects['tournament_appeal']->getId(),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('table tbody tr');
                static::assertSelectorExists('[data-appeal-resolve]');
                static::assertSelectorExists('textarea.appeal-verdict-input');
            },
        ];

        yield 'non-jury gets 403' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/my/appeals/' . $objects['tournament_appeal']->getId(),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];
    }
}
