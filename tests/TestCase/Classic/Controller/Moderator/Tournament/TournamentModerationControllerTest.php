<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Moderator\Tournament;

use App\Classic\Entity\TournamentModerationClaim;
use App\Classic\Enum\TournamentModerationStatus;
use App\Classic\Service\TournamentModerationService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TournamentModerationControllerTest extends WebTestCase
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
            'Entity/tournament_moderation.yaml',
        ];

        yield 'list requires moderator' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/tournaments'),
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'list shows pending claims' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/moderator/tournaments'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Турнір на модерації', $client->getCrawler()->text());
            },
        ];

        yield 'approve tournament' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_pending']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Approved, $claim->getStatus());
            },
        ];

        yield 'reject tournament with comment' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'Назва не підходить'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Rejected, $claim->getStatus());
                static::assertSame('Назва не підходить', $claim->getComment());
            },
        ];

        yield 'approve tournament without claim returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_no_claim']->getId() . '/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject tournament without claim returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_no_claim']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'Тест'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve non-existent tournament returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/tournaments/999999/approve',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject non-existent tournament returns 404' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request(
                'POST',
                '/moderator/tournaments/999999/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'approve throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_pending']->getId() . '/approve',
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
                $stub->method('approve')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentModerationService::class, $stub);
            },
        ];

        yield 'reject throwable returns 500' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/moderator/tournaments/' . $objects['tournament_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function () {
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(TournamentModerationService::class);
                $stub->method('reject')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(TournamentModerationService::class, $stub);
            },
        ];

        yield 'double approve returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $client->request('POST', "/moderator/tournaments/$id/approve", [], [], ['CONTENT_TYPE' => 'application/json']);
                $client->request('POST', "/moderator/tournaments/$id/approve", [], [], ['CONTENT_TYPE' => 'application/json']);
            },
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Approved, $claim->getStatus());
            },
        ];

        yield 'double reject returns 422' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static function (KernelBrowser $client, array $objects) {
                $id = $objects['tournament_pending']->getId();
                $client->request('POST', "/moderator/tournaments/$id/reject", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['comment' => 'Перший'], JSON_THROW_ON_ERROR));
                $client->request('POST', "/moderator/tournaments/$id/reject", [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['comment' => 'Другий'], JSON_THROW_ON_ERROR));
            },
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(TournamentModerationClaim::class)
                    ->findOneBy(['tournament' => $objects['tournament_pending']->getId()]);
                static::assertSame(TournamentModerationStatus::Rejected, $claim->getStatus());
                static::assertSame('Перший', $claim->getComment());
            },
        ];

        yield 'show page visible to moderator for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];

        yield 'show page hidden from regular user for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_regular',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'show page visible to owner for unpublished' => [
            'fixtures' => $fixtures,
            'loginAs' => 'user_creator',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request('GET', '/tournament/' . $objects['tournament_pending']->getId()),
            'expectedStatus' => 200,
            'afterCallback' => static function () {
            },
        ];
    }
}
