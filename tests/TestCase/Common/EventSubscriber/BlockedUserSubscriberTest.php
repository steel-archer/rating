<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\EventSubscriber;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlockedUserSubscriberTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testBlockedUser(
        array $fixtures,
        string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);
        $client->loginUser($objects[$loginAs]);

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        $fixtures = ['Entity/base.yaml', 'Entity/users.yaml', 'Entity/user_blocked.yaml'];

        yield 'blocked user sees blocked page on home' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_blocked',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/'),
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Порушення правил сайту', $client->getResponse()->getContent());
                static::assertStringContainsString('Обліковий запис заблоковано', $client->getResponse()->getContent());
            },
        ];

        yield 'blocked user sees blocked page on players list' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_blocked',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Порушення правил сайту', $client->getResponse()->getContent());
            },
        ];

        yield 'blocked user sees blocked page on API request' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_blocked',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['telegram' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Обліковий запис заблоковано', $client->getResponse()->getContent());
            },
        ];

        yield 'blocked user can access logout' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_blocked',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/logout'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'blocked user can access license' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_blocked',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/license'),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'active user is not blocked' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_player',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringNotContainsString('Обліковий запис заблоковано', $client->getResponse()->getContent());
            },
        ];
    }
}
