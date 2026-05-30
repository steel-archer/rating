<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Moderator\User;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlockPageControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testBlockPage(
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
        $fixtures = ['Entity/base.yaml', 'Entity/users.yaml', 'Entity/user_blocked.yaml'];

        yield 'moderator sees block form for active user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Причина блокування', $client->getCrawler()->text());
                static::assertCount(1, $client->getCrawler()->filter('#block-form'));
            },
        ];

        yield 'moderator sees unblock option for blocked user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/moderator/users/' . $objects['user_blocked']->getId() . '/block',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Порушення правил сайту', $client->getCrawler()->text());
                static::assertStringContainsString('Розблокувати', $client->getCrawler()->text());
                static::assertCount(0, $client->getCrawler()->filter('#block-form'));
            },
        ];

        yield 'non-existent user returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/users/999999/block'),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => $fixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
