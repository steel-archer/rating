<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator\Venue;

use App\Entity\Venue;
use App\Entity\VenueRepresentative;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueModerationControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

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
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $crawler = $client->request('GET', '/moderator/venues');
                $token = self::extractCsrfToken($crawler, "/moderator/venues/$id/approve");
                $client->request('POST', "/moderator/venues/$id/approve", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertTrue($venue->isApproved());
            },
        ];

        yield 'double approve redirects with flash' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $crawler = $client->request('GET', '/moderator/venues');
                $token = self::extractCsrfToken($crawler, "/moderator/venues/$id/approve");
                $client->request('POST', "/moderator/venues/$id/approve", ['_token' => $token]);
                $client->request('POST', "/moderator/venues/$id/approve", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject venue deletes it' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $crawler = $client->request('GET', '/moderator/venues');
                $token = self::extractCsrfToken($crawler, "/moderator/venues/$id/reject");
                $client->request('POST', "/moderator/venues/$id/reject", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertNull($venue);

                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $objects['venue_pending']->getId()]);
                static::assertCount(0, $reps);
            },
        ];

        yield 'reject after approve fails with flash' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $crawler = $client->request('GET', '/moderator/venues');
                $approveToken = self::extractCsrfToken($crawler, "/moderator/venues/$id/approve");
                $rejectToken = self::extractCsrfToken($crawler, "/moderator/venues/$id/reject");
                $client->request('POST', "/moderator/venues/$id/approve", ['_token' => $approveToken]);
                $client->request('POST', "/moderator/venues/$id/reject", ['_token' => $rejectToken]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertNotNull($venue);
                static::assertTrue($venue->isApproved());
            },
        ];

        yield 'double reject returns 404 (venue deleted)' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['venue_pending']->getId();
                $crawler = $client->request('GET', '/moderator/venues');
                $token = self::extractCsrfToken($crawler, "/moderator/venues/$id/reject");
                $client->request('POST', "/moderator/venues/$id/reject", ['_token' => $token]);
                $client->request('POST', "/moderator/venues/$id/reject", ['_token' => $token]);
            },
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
    }
}
