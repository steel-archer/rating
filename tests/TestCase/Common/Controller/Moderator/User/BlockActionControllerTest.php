<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Moderator\User;

use App\Common\Entity\User;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlockActionControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testBlockAction(
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

        yield 'moderator blocks user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Порушення правил'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)
                    ->find($objects['user_with_player']->getId());
                static::assertTrue($user->isBlocked());
                static::assertSame('Порушення правил', $user->getBlockedReason());
            },
        ];

        yield 'moderator cannot block self' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_admin']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Самоблокування'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('block.error.self', $response['error']);
            },
        ];

        yield 'cannot block moderator' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_moderator']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Спроба заблокувати модератора'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('block.error.moderator', $response['error']);
            },
        ];

        yield 'too short reason returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'ab'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'empty reason returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => ''], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'non-existent user returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/users/999999/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Причина блокування'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'regular user gets 403' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Причина блокування'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => $fixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_with_player']->getId() . '/block',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['reason' => 'Причина блокування'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
