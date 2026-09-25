<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\My\Venue;

use App\Common\Entity\Venue;
use App\Common\Entity\VenueRepresentative;
use App\Common\Service\VenueManagementService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VenueControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testVenue(
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

        yield 'list requires player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/venues'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'list requires login' => [
            'fixtures' => $fixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/venues'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'list shows venues' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/venues'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Новий майданчик', $client->getCrawler()->text());
                static::assertStringContainsString('Мій схвалений майданчик', $client->getCrawler()->text());
            },
        ];

        yield 'create form prefills player town and country' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/venues/new'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $crawler = $client->getCrawler();
                // Defaults are taken from the creator's player (Franko -> Lviv, Ukraine).
                static::assertSame('Львів', $crawler->filter('#town')->attr('value'));
                static::assertSame('Україна', $crawler->filter('#country')->attr('value'));
            },
        ];

        yield 'create form denied without player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/venues/new'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'create venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Тестовий майданчик',
                    'townId' => $objects['town_kyiv']->getId(),
                    'description' => 'Затишне місце для гри',
                    'url' => 'https://venue.example.com',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function () {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->findOneBy(['name' => 'Тестовий майданчик']);
                static::assertNotNull($venue);
                static::assertFalse($venue->isApproved());
                static::assertSame('Затишне місце для гри', $venue->getDescription());
                static::assertSame('https://venue.example.com', $venue->getUrl());

                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $venue]);
                static::assertCount(1, $reps);
            },
        ];

        yield 'create with invalid url fails validation' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Майданчик з поганим лінком',
                    'townId' => $objects['town_kyiv']->getId(),
                    'url' => 'not-a-valid-url',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->findOneBy(['name' => 'Майданчик з поганим лінком']);
                static::assertNull($venue);
            },
        ];

        yield 'create duplicate venue fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Новий майданчик',
                    'townId' => $objects['town_kyiv']->getId(),
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('venue.error.duplicate', $json['error']);
            },
        ];

        yield 'create with invalid town fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Тест',
                    'townId' => 999999,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('venue.error.town_not_found', $json['error']);
            },
        ];

        yield 'create denied without player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Hack',
                    'townId' => $objects['town_kyiv']->getId(),
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'create online venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Онлайн-квіз',
                    'isOnline' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function () {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->findOneBy(['name' => 'Онлайн-квіз']);
                static::assertNotNull($venue);
                static::assertTrue($venue->isOnline());
                static::assertSame('Онлайн', $venue->getTown()->getName());
                static::assertFalse($venue->isApproved());

                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $venue]);
                static::assertCount(1, $reps);
            },
        ];

        yield 'create online venue ignores townId' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Онлайн-квіз 2',
                    'isOnline' => true,
                    'townId' => $objects['town_kyiv']->getId(),
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function () {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->findOneBy(['name' => 'Онлайн-квіз 2']);
                static::assertNotNull($venue);
                static::assertTrue($venue->isOnline());
                static::assertSame('Онлайн', $venue->getTown()->getName());
            },
        ];

        yield 'create duplicate online venue fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Існуючий онлайн-майданчик',
                    'isOnline' => true,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('venue.error.duplicate', $json['error']);
            },
        ];

        yield 'create offline venue without townId fails validation' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Без міста',
                    'isOnline' => false,
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('venue.error.town_required', $json['error']);
            },
        ];

        yield 'edit page shown for approved venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/my/venues/' . $objects['venue_approved_owned']->getId() . '/edit',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Мій схвалений майданчик', $client->getCrawler()->text());
            },
        ];

        yield 'edit page shown for pending venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/my/venues/' . $objects['venue_pending']->getId() . '/edit',
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                // Description and url are editable while pending; representatives are not yet.
                $crawler = $client->getCrawler();
                static::assertCount(1, $crawler->filter('textarea[name="description"]'));
                static::assertCount(1, $crawler->filter('input[name="url"]'));
                static::assertCount(0, $crawler->filter('[data-role="representatives"]'));
            },
        ];

        yield 'edit page denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'GET',
                '/my/venues/' . $objects['venue_approved_owned']->getId() . '/edit',
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'update representatives' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues/' . $objects['venue_approved_owned']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'description' => 'Оновлений опис',
                    'url' => 'https://approved.example.com',
                    'representatives' => [
                        $objects['player_franko']->getId(),
                        $objects['player_shevchenko']->getId(),
                    ],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $objects['venue_approved_owned']->getId()]);
                static::assertCount(2, $reps);

                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_approved_owned']->getId());
                static::assertSame('Оновлений опис', $venue->getDescription());
                static::assertSame('https://approved.example.com', $venue->getUrl());
            },
        ];

        yield 'update cannot remove creator from representatives' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues/' . $objects['venue_approved_owned']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'representatives' => [$objects['player_shevchenko']->getId()],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $objects['venue_approved_owned']->getId()]);
                static::assertCount(2, $reps);
            },
        ];

        yield 'update denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues/' . $objects['venue_approved_owned']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['representatives' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $reps = static::getContainer()->get('doctrine')
                    ->getRepository(VenueRepresentative::class)
                    ->findBy(['venue' => $objects['venue_approved_owned']->getId()]);
                static::assertCount(1, $reps);
            },
        ];

        yield 'update non-existent venue' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/venues/999999',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['representatives' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'update pending venue info succeeds' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues/' . $objects['venue_pending']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'description' => 'Опис очікуваного майданчика',
                    'url' => 'https://pending.example.com',
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $venue = static::getContainer()->get('doctrine')
                    ->getRepository(Venue::class)
                    ->find($objects['venue_pending']->getId());
                static::assertSame('Опис очікуваного майданчика', $venue->getDescription());
                static::assertSame('https://pending.example.com', $venue->getUrl());
                static::assertFalse($venue->isApproved());
            },
        ];

        yield 'store throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Throwable', 'townId' => $objects['town_kyiv']->getId()], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(VenueManagementService::class);
                $stub->method('create')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(VenueManagementService::class, $stub);
            },
        ];

        yield 'update throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_venue_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/venues/' . $objects['venue_approved_owned']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['representatives' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(VenueManagementService::class);
                $stub->method('update')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(VenueManagementService::class, $stub);
            },
        ];
    }
}
