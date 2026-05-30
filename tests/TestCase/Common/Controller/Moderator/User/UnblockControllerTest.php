<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Moderator\User;

use App\Common\Entity\User;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnblockControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testUnblock(
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

        yield 'moderator unblocks user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/users/' . $objects['user_blocked']->getId() . '/unblock',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $user = static::getContainer()->get('doctrine')
                    ->getRepository(User::class)
                    ->find($objects['user_blocked']->getId());
                static::assertFalse($user->isBlocked());
                static::assertNull($user->getBlockedReason());
            },
        ];

        yield 'non-existent user returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/users/999999/unblock',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
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
                '/moderator/users/' . $objects['user_blocked']->getId() . '/unblock',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
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
                '/moderator/users/' . $objects['user_blocked']->getId() . '/unblock',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
