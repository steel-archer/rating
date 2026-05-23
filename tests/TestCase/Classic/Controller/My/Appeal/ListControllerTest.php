<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\Appeal;

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
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', '/my/appeals');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'jury member sees tournament in list' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('table tbody tr');
                static::assertSelectorTextContains('table tbody tr td a', 'Турнір з апеляціями');
            },
        ];

        yield 'non-jury sees empty list' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorNotExists('table');
            },
        ];

        yield 'anonymous gets redirect' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
