<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament\Appeal;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListControllerTest extends WebTestCase
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
    public function testList(
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
        yield 'player sees appeals table with existing appeal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('table tbody tr');
                static::assertSelectorTextContains('table tbody tr td', 'На зняття');
            },
        ];

        yield 'jury sees appeals' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('table tbody tr');
            },
        ];

        yield 'submit button shown when appeal deadline open' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('a.btn');
            },
        ];

        yield 'anonymous gets redirect' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals',
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
