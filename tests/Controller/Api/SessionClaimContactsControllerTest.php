<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SessionClaimContactsControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSessionContacts(
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
        yield 'organizer sees contacts' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/api/sessions/' . $objects['session_pending']->getId() . '/contacts',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertArrayHasKey('email', $data);
                static::assertSame('representative@example.com', $data['email']);
            },
        ];

        yield 'moderator sees contacts' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_moderator_sc',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/api/sessions/' . $objects['session_pending']->getId() . '/contacts',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertArrayHasKey('email', $data);
            },
        ];

        yield 'non-organizer gets 403' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_other',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/api/sessions/' . $objects['session_pending']->getId() . '/contacts',
            ),
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('forbidden', $data['error']);
            },
        ];

        yield 'organizer sees empty contacts for representative without user' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/api/sessions/' . $objects['session_no_user']->getId() . '/contacts',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('', $data['email']);
                static::assertNull($data['telegram']);
                static::assertNull($data['facebook']);
                static::assertNull($data['phone']);
            },
        ];

        yield 'non-existent session returns 404' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'GET',
                '/api/sessions/999999/contacts',
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('not_found', $data['error']);
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/session_claims.yaml'],
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/api/sessions/' . $objects['session_pending']->getId() . '/contacts',
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];
    }
}
