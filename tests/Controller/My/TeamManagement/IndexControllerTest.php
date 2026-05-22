<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\TeamManagement;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
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
    public function testIndex(
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

        $client->request('GET', '/my/team');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'requires login' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'captain sees full page' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_captain',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $text = $client->getCrawler()->text();
                static::assertStringContainsString('Альфа', $text);
                static::assertStringContainsString('Шевченко', $text);
                static::assertStringContainsString('Франко', $text);
                static::assertStringContainsString('Зробити капітаном', $text);
            },
        ];

        yield 'member sees read-only page' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_member',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $text = $client->getCrawler()->text();
                static::assertStringContainsString('Альфа', $text);
                static::assertStringContainsString('Піти з команди', $text);
                static::assertStringNotContainsString('Зробити капітаном', $text);
            },
        ];

        yield 'outsider sees empty state' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_outsider',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('не входите до базового складу', $client->getCrawler()->text());
            },
        ];
    }
}
