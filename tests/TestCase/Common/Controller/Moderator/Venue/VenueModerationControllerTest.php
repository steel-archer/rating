<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Moderator\Venue;

use App\Common\Entity\Venue;
use App\Common\Entity\VenueRepresentative;
use App\Common\Service\VenueManagementService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueModerationControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testModeration(
        array $fixtures,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $mockSetup = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($mockSetup !== null) {
            $mockSetup($this, $client);
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
        $fixtures = [
            'Entity/base.yaml',
            'Entity/users.yaml',
            'Entity/my_venues.yaml',
        ];

        yield 'list requires moderator' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/venues'),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'list shows pending venues' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/venues'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Новий майданчик', $client->getCrawler()->text());
                static::assertStringNotContainsString('Мій схвалений майданчик', $client->getCrawler()->text());
            },
        ];

        yield 'approve venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/venues/' . $objects['venue_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertTrue($venue->isApproved());
            },
        ];

        yield 'double approve returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $client->request('POST', "/moderator/venues/$id/approve", [], [], ['CONTENT_TYPE' => 'application/json']);
                $client->request('POST', "/moderator/venues/$id/approve", [], [], ['CONTENT_TYPE' => 'application/json']);
            },
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject venue deletes it' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/venues/' . $objects['venue_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $objects['venue_kyiv']->getId()]);
                // venue_pending was deleted, check via direct query
                $connection = static::getContainer()->get('doctrine')->getConnection();
                $count = $connection->fetchOne(
                    'SELECT COUNT(*) FROM venue WHERE name = ?',
                    ['Новий майданчик'],
                );
                static::assertSame(0, (int) $count);
            },
        ];

        yield 'reject after approve fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $client->request('POST', "/moderator/venues/$id/approve", [], [], ['CONTENT_TYPE' => 'application/json']);
                $client->request('POST', "/moderator/venues/$id/reject", [], [], ['CONTENT_TYPE' => 'application/json']);
            },
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertNotNull($venue);
                static::assertTrue($venue->isApproved());
            },
        ];

        yield 'approve non-existent venue returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/venues/999999/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject non-existent venue returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/venues/999999/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => $fixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/venues'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/venues/' . $objects['venue_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(VenueManagementService::class);
                $stub->method('approve')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(VenueManagementService::class, $stub);
            },
        ];

        yield 'reject throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/venues/' . $objects['venue_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(VenueManagementService::class);
                $stub->method('reject')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(VenueManagementService::class, $stub);
            },
        ];
    }
}
