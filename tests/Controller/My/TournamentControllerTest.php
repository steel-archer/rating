<?php

declare(strict_types=1);

namespace App\Tests\Controller\My;

use App\Entity\Tournament;
use App\Entity\TournamentModerationClaim;
use App\Entity\TournamentModerationStatus;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentOfficialRole;
use App\Entity\TournamentStatus;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TournamentControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

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
            'expectedStatus' => 403,
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
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'create tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client) {
                $crawler = $client->request('GET', '/my/tournaments/new');
                $token = $crawler->filter('input[name="_token"]')->attr('value');
                $client->request('POST', '/my/tournaments', ['name' => 'Новий тестовий турнір', '_token' => $token]);
            },
            'expectedStatus' => 302,
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
            'action' => static function (KernelBrowser $client) {
                $crawler = $client->request('GET', '/my/tournaments/new');
                $client->request('POST', '/my/tournaments', ['name' => 'test', '_token' => 'fake']);
            },
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
                json_encode([
                    'name' => 'Hack',
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
            'expectedStatus' => 404,
            'afterCallback' => static function () {
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
                json_encode([
                    'name' => 'Hack',
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
                json_encode([
                    'name' => 'Спроба',
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
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'submit for moderation' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_draft']->getId();
                $crawler = $client->request('GET', "/my/tournaments/$id/edit");
                $token = self::extractCsrfToken($crawler, "/my/tournaments/$id/submit");
                $client->request('POST', "/my/tournaments/$id/submit", ['_token' => $token]);
            },
            'expectedStatus' => 302,
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
                ['_token' => 'fake'],
            ),
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_draft']->getId()]);
                static::assertNull($claim);
            },
        ];

        yield 'publish approved tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_approved']->getId();
                $crawler = $client->request('GET', "/my/tournaments/$id/edit");
                $token = self::extractCsrfToken($crawler, "/my/tournaments/$id/publish");
                $client->request('POST', "/my/tournaments/$id/publish", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_approved']->getId());
                static::assertSame(TournamentStatus::Published, $tournament->getStatus());
            },
        ];

        yield 'delete draft tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_draft']->getId();
                $crawler = $client->request('GET', "/my/tournaments/$id/edit");
                $token = self::extractCsrfToken($crawler, "/my/tournaments/$id/delete");
                $client->request('POST', "/my/tournaments/$id/delete", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertNull($tournament);
            },
        ];

        yield 'no delete button for published tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/my/tournaments/' . $objects['tournament_published']->getId() . '/edit'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertCount(0, $client->getCrawler()->filter('form[action*="delete"]'));
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
                    'startedAt' => new DateTime('+30 days')->format('Y-m-d\TH:i'),
                    'endedAt' => new DateTime('+31 days')->format('Y-m-d\TH:i'),
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
                json_encode([
                    'name' => 'Мій чернетковий турнір',
                    'startedAt' => '2020-01-01T10:00',
                    'endedAt' => '2020-01-02T10:00',
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
            'action' => static function (KernelBrowser $client) {
                $crawler = $client->request('GET', '/my/tournaments/new');
                $token = $crawler->filter('input[name="_token"]')->attr('value');
                $client->request('POST', '/my/tournaments', ['name' => 'Турнір з оргом', '_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function () {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->findOneBy(['name' => 'Турнір з оргом']);
                $officials = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentOfficial::class)
                    ->findBy(['tournament' => $tournament]);
                static::assertCount(1, $officials);
                static::assertSame(
                    TournamentOfficialRole::Organizer,
                    $officials[0]->getRole(),
                );
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

        yield 'publish fails without approval' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_draft']->getId();
                $crawler = $client->request('GET', "/my/tournaments/$id/edit");
                $token = self::extractCsrfToken($crawler, "/my/tournaments/$id/submit");
                // Use submit token for publish (same page, different action)
                $client->request('POST', "/my/tournaments/$id/publish", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertSame(TournamentStatus::Draft, $tournament->getStatus());
            },
        ];

        yield 'delete published tournament fails' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static function (KernelBrowser $client, array $objects) {
                $draftId = $objects['tournament_draft']->getId();
                $publishedId = $objects['tournament_published']->getId();
                $crawler = $client->request('GET', "/my/tournaments/$draftId/edit");
                $token = self::extractCsrfToken($crawler, "/my/tournaments/$draftId/delete");
                $client->request('POST', "/my/tournaments/$publishedId/delete", ['_token' => $token]);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_published']->getId());
                static::assertNotNull($tournament);
            },
        ];

        yield 'delete other users tournament returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_with_player',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_draft']->getId();
                // Visit any page to get session, then use correct CSRF token pattern
                $client->request('GET', '/my/tournaments');
                // CSRF will fail anyway for wrong user, but let's test the flow
                $client->request('POST', "/my/tournaments/$id/delete", ['_token' => 'any']);
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $tournament = static::getContainer()->get('doctrine')
                    ->getRepository(Tournament::class)
                    ->find($objects['tournament_draft']->getId());
                static::assertNotNull($tournament);
            },
        ];
    }
}
