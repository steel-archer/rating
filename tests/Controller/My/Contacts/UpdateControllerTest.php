<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\Contacts;

use App\Entity\User;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testUpdateContacts(
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
        $afterCallback($objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'saves valid contacts' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'telegram' => 'test_user',
                    'facebook' => 'test.user.123',
                    'phone' => '+380501234567',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertSame('test_user', $user->getTelegram());
                static::assertSame('test.user.123', $user->getFacebook());
                static::assertSame('+380501234567', $user->getPhone());
            },
        ];

        yield 'saves null contacts' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'telegram' => null,
                    'facebook' => null,
                    'phone' => null,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getTelegram());
                static::assertNull($user->getFacebook());
                static::assertNull($user->getPhone());
            },
        ];

        yield 'invalid telegram returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['telegram' => 'ab'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getTelegram());
            },
        ];

        yield 'invalid facebook returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['facebook' => 'invalid user name with spaces!'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getFacebook());
            },
        ];

        yield 'invalid phone returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['phone' => '123'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getPhone());
            },
        ];

        yield 'russian phone returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['phone' => '+79161234567'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getPhone());
            },
        ];

        yield 'belarusian phone returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['phone' => '+375291234567'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getPhone());
            },
        ];

        yield 'kazakh phone is allowed' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['phone' => '+77011234567'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'parseable but invalid phone returns 422' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['phone' => '+380000000000'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getPhone());
            },
        ];

        yield 'xss in telegram is rejected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['telegram' => '<script>alert(1)</script>'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getTelegram());
            },
        ];

        yield 'sql injection in facebook is rejected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['facebook' => "'; DROP TABLE foo; --"], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getFacebook());
            },
        ];

        yield 'javascript url in telegram is rejected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['telegram' => 'javascript:alert(1)'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getTelegram());
            },
        ];

        yield 'path traversal in facebook is rejected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
                json_encode(['facebook' => '../../../etc/passwd'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)->find($objects['user_with_player']->getId());
                static::assertNull($user->getFacebook());
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['telegram' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'user without player gets 302' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/contacts',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['telegram' => 'test_user'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
