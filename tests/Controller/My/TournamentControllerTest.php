<?php

declare(strict_types=1);

namespace App\Tests\Controller\My;

use App\Entity\Tournament;
use App\Entity\TournamentModerationClaim;
use App\Enum\TournamentModerationStatus;
use App\Entity\TournamentOfficial;
use App\Enum\TournamentOfficialRole;
use App\Enum\TournamentStatus;
use App\Service\TournamentManagementService;
use App\Service\TournamentModerationService;
use App\Tests\Controller\My\Tournament\DocumentTestTrait;
use App\Tests\FixturesTrait;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TournamentControllerTest extends WebTestCase
{
    use DocumentTestTrait;
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testTournament(
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
            'Entity/my_tournaments.yaml',
        ];
        $fixturesStarted = [
            'Entity/base.yaml',
            'Entity/users.yaml',
            'Entity/my_tournaments_started.yaml',
        ];

        yield 'list requires login' => [
            'fixtures' => $fixtures,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'list requires player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'list shows tournaments' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertGreaterThanOrEqual(2, $client->getCrawler()->filter('table tbody tr')->count());
            },
        ];

        yield 'create form shown' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments/new'),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'create form denied without player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments/new'),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'create tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Новий тестовий турнір'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Новий тестовий турнір']);
                static::assertNotNull($tournament);
                static::assertSame(TournamentStatus::Draft, $tournament->getStatus());
            },
        ];

        yield 'store denied without player' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'test']);
                static::assertNull($tournament);
            },
        ];

        yield 'edit page shown to owner' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/my/tournaments/' . $objects['tournament_draft']->getId() . '/edit'),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'edit page denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/my/tournaments/' . $objects['tournament_draft']->getId() . '/edit'),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'update tournament via JSON' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Оновлена назва',
                    'startedAt' => null,
                    'endedAt' => null,
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);
            },
        ];

        yield 'update denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Hack', 'startedAt' => null, 'endedAt' => null, 'toursCount' => null, 'questionsPerTour' => null, 'difficulty' => null, 'organizers' => [], 'editors' => [], 'gameJury' => [], 'appealJury' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertSame('Мій чернетковий турнір', $tournament->getName());
            },
        ];

        yield 'update non-existent tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments/999999',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Hack', 'startedAt' => null, 'endedAt' => null, 'toursCount' => null, 'questionsPerTour' => null, 'difficulty' => null, 'organizers' => [], 'editors' => [], 'gameJury' => [], 'appealJury' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'edit non-existent tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/my/tournaments/999999/edit'),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'update denied for started published tournament' => [
            'fixtures' => $fixturesStarted,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_started']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Спроба', 'startedAt' => null, 'endedAt' => null, 'toursCount' => null, 'questionsPerTour' => null, 'difficulty' => null, 'organizers' => [], 'editors' => [], 'gameJury' => [], 'appealJury' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'submit for moderation' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_draft']->getId()]);
                static::assertNotNull($claim);
                static::assertSame(TournamentModerationStatus::Pending, $claim->getStatus());
            },
        ];

        yield 'submit denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_draft']->getId()]);
                static::assertNull($claim);
            },
        ];

        yield 'submit already approved tournament returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'submit published tournament fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_published']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'submit non-existent tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments/999999/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'publish approved tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId() . '/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_approved']->getId());
                static::assertSame(TournamentStatus::Published, $tournament->getStatus());
            },
        ];

        yield 'publish already published tournament returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_published']->getId() . '/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'publish fails without approval' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertSame(TournamentStatus::Draft, $tournament->getStatus());
            },
        ];

        yield 'publish non-existent tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments/999999/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'publish denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId() . '/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_approved']->getId());
                static::assertSame(TournamentStatus::Draft, $tournament->getStatus());
            },
        ];

        yield 'delete draft tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Мій чернетковий турнір']);
                static::assertNull($tournament);
            },
        ];

        yield 'delete draft tournament with documents' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                // Upload a document first
                $client->request(
                    'POST',
                    '/my/tournaments/' . $objects['tournament_draft']->getId() . '/documents',
                    [],
                    ['file' => self::createTestPdf()],
                );
                // Then delete the tournament
                $client->request(
                    'POST',
                    '/my/tournaments/' . $objects['tournament_draft']->getId() . '/delete',
                    [],
                    [],
                    ['CONTENT_TYPE' => 'application/json'],
                );
            },
            'expectedStatus' => 200,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Мій чернетковий турнір']);
                static::assertNull($tournament);
            },
        ];

        yield 'delete draft tournament with claim' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Схвалений турнір']);
                static::assertNull($tournament);
            },
        ];

        yield 'delete published tournament fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_published']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_published']->getId());
                static::assertNotNull($tournament);
            },
        ];

        yield 'delete denied for other user' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertNotNull($tournament);
            },
        ];

        yield 'no delete button for published tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/my/tournaments/' . $objects['tournament_published']->getId() . '/edit'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertCount(0, $client->getCrawler()->filter('[data-tournament-delete]'));
            },
        ];

        yield 'update with name change triggers moderation reset' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Змінена назва',
                    'startedAt' => (new DateTime('+30 days'))->format('Y-m-d'),
                    'endedAt' => (new DateTime('+31 days'))->format('Y-m-d'),
                    'toursCount' => 3,
                    'questionsPerTour' => 12,
                    'difficulty' => 3.5,
                    'organizers' => [],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_approved']->getId());
                static::assertSame(TournamentStatus::Draft, $tournament->getStatus());
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_approved']->getId()]);
                static::assertSame(TournamentModerationStatus::Pending, $claim->getStatus());
            },
        ];

        yield 'update with dates in past returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Мій чернетковий турнір', 'startedAt' => '2020-01-01', 'endedAt' => '2020-01-02', 'toursCount' => null, 'questionsPerTour' => null, 'difficulty' => null, 'organizers' => [], 'editors' => [], 'gameJury' => [], 'appealJury' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'update with end before start returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => (new DateTime('+60 days'))->format('Y-m-d'),
                    'endedAt' => (new DateTime('+30 days'))->format('Y-m-d'),
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'update with dates spanning multiple seasons returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => '2026-09-29',
                    'endedAt' => '2026-10-02',
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'update with resultsHiddenUntil before endedAt returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => (new DateTime('+30 days'))->format('Y-m-d'),
                    'endedAt' => (new DateTime('+31 days'))->format('Y-m-d'),
                    'resultsHiddenUntil' => (new DateTime('+30 days'))->format('Y-m-d'),
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'create adds creator as organizer' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Турнір з оргом'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 201,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Турнір з оргом']);
                $officials = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentOfficial::class)
                    ->findBy(['tournament' => $tournament]);
                static::assertCount(1, $officials);
                static::assertSame(TournamentOfficialRole::Organizer, $officials[0]->getRole());
            },
        ];

        yield 'store throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/my/tournaments',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'Throwable'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $json['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentManagementService::class);
                $stub->method('create')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentManagementService::class, $stub);
            },
        ];

        yield 'submit throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/submit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentModerationService::class);
                $stub->method('submitForModeration')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentModerationService::class, $stub);
            },
        ];

        yield 'publish throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_approved']->getId() . '/publish',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentManagementService::class);
                $stub->method('publish')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentManagementService::class, $stub);
            },
        ];

        yield 'delete throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId() . '/delete',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentManagementService::class);
                $stub->method('delete')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentManagementService::class, $stub);
            },
        ];

        yield 'update throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['name' => 'x', 'startedAt' => null, 'endedAt' => null, 'toursCount' => null, 'questionsPerTour' => null, 'difficulty' => null, 'organizers' => [], 'editors' => [], 'gameJury' => [], 'appealJury' => []], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentManagementService::class);
                $stub->method('update')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentManagementService::class, $stub);
            },
        ];

        yield 'update with new officials adds them' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => null,
                    'endedAt' => null,
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [$objects['player_franko']->getId()],
                    'editors' => [$objects['player_shevchenko']->getId()],
                    'gameJury' => [$objects['player_lesya']->getId()],
                    'appealJury' => [$objects['player_franko']->getId()],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $officials = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentOfficial::class)
                    ->findBy(['tournament' => $objects['tournament_draft']->getId()]);
                static::assertCount(4, $officials);
            },
        ];

        yield 'update with non-existent player id ignores it' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournaments/' . $objects['tournament_draft']->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => null,
                    'endedAt' => null,
                    'toursCount' => null,
                    'questionsPerTour' => null,
                    'difficulty' => null,
                    'organizers' => [$objects['player_franko']->getId(), 999999],
                    'editors' => [],
                    'gameJury' => [],
                    'appealJury' => [],
                ], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $officials = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentOfficial::class)
                    ->findBy(['tournament' => $objects['tournament_draft']->getId()]);
                static::assertCount(1, $officials);
            },
        ];
    }
}
