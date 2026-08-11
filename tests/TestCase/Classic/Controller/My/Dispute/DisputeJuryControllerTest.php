<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\Dispute;

use App\Classic\Service\SessionResultService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DisputeJuryControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/disputes.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('listProvider')]
    public function testList(
        array $fixtures,
        ?string $loginAs,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', '/my/disputes');

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function listProvider(): iterable
    {
        yield 'jury member sees list' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'expectedStatus' => 200,
        ];

        yield 'non-jury sees empty list' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'expectedStatus' => 200,
        ];

        yield 'anonymous gets redirect' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'expectedStatus' => 302,
        ];
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('tournamentProvider')]
    public function testTournament(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function tournamentProvider(): iterable
    {
        yield 'jury member sees tournament disputes' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/' . $objects['tournament_dispute']->getId(),
            'expectedStatus' => 200,
        ];

        yield 'non-jury gets 403' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_other',
            'uri' => static fn(array $objects) => '/my/disputes/' . $objects['tournament_dispute']->getId(),
            'expectedStatus' => 403,
        ];
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('resolveProvider')]
    public function testResolve(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        callable $payload,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request(
            'POST',
            $uri($objects),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload($objects), JSON_THROW_ON_ERROR),
        );

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function resolveProvider(): iterable
    {
        yield 'jury accepts dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
                'comment' => 'прийнято',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'jury rejects dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'reject',
                'comment' => 'відхилено',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'jury rejects without comment' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'reject',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'non-jury cannot resolve' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_other',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'cannot resolve non-submitted dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_beta_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.already_resolved', $body['error']);
            },
        ];

        yield 'cannot resolve dispute twice' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                // Try to resolve again
                $client->request(
                    'POST',
                    '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
                    [],
                    [],
                    ['CONTENT_TYPE' => 'application/json'],
                    json_encode(['action' => 'reject'], JSON_THROW_ON_ERROR),
                );
                static::assertResponseStatusCodeSame(422);
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.already_resolved', $body['error']);
            },
        ];
    }

    public function testRejectInvalidatesTournamentCache(): void
    {
        $client = static::createClient();
        $objects = self::loadFixtures(self::FIXTURES);

        $tournament = $objects['tournament_dispute'];
        $alphaTeamId = $objects['session_team_dispute_alpha']->getId();
        $sessionResultService = static::getContainer()->get(SessionResultService::class);

        // Prime the cache: question 3 for team "alpha" has a submitted (unresolved) dispute.
        $breakdownBefore = $sessionResultService->getTournamentAnswerBreakdown($tournament);
        static::assertSame('?', $breakdownBefore[$alphaTeamId]->answers[2]);

        $client->loginUser($objects['user_dispute_jury']);
        $client->request(
            'POST',
            '/my/disputes/resolve/' . $objects['answer_dispute_alpha_3']->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['action' => 'reject', 'comment' => 'відхилено'], JSON_THROW_ON_ERROR),
        );

        static::assertResponseStatusCodeSame(200);

        // Rejecting resolves the dispute, so the breakdown should now show the underlying (incorrect)
        // answer instead of the stale "under dispute" marker - proving the cache was invalidated.
        $breakdownAfter = $sessionResultService->getTournamentAnswerBreakdown($tournament);
        static::assertSame(0, $breakdownAfter[$alphaTeamId]->answers[2]);
    }
}
