<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim\Dispute;

use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\DisputeStatus;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DisputeControllerTest extends WebTestCase
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
    public static function listProvider(): iterable
    {
        yield 'organizer can view disputes page' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes',
            'expectedStatus' => 200,
        ];

        yield 'non-owner gets 403' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes',
            'expectedStatus' => 403,
        ];

        yield 'anonymous gets redirect' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes',
            'expectedStatus' => 302,
        ];
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('createProvider')]
    public function testCreate(
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
    public static function createProvider(): iterable
    {
        yield 'create dispute on incorrect answer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => $objects['session_team_dispute_beta']->getId(),
                'questionNumber' => 2,
                'text' => 'нова спірна',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'cannot create dispute on correct answer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => $objects['session_team_dispute_alpha']->getId(),
                'questionNumber' => 1,
                'text' => 'спірна на правильну',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.already_correct', $body['error']);
            },
        ];

        yield 'cannot create dispute when one already exists' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => $objects['session_team_dispute_alpha']->getId(),
                'questionNumber' => 3,
                'text' => 'дублікат',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.already_exists', $body['error']);
            },
        ];

        yield 'create dispute with empty text' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => $objects['session_team_dispute_beta']->getId(),
                'questionNumber' => 2,
                'text' => '',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'cannot create dispute for unknown team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => 999999,
                'questionNumber' => 1,
                'text' => 'test',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('common.not_found', $body['error']);
            },
        ];

        yield 'cannot create dispute for nonexistent question' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/create',
            'payload' => static fn(array $objects) => [
                'sessionTeamId' => $objects['session_team_dispute_alpha']->getId(),
                'questionNumber' => 99,
                'text' => 'test',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('common.not_found', $body['error']);
            },
        ];
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('submitProvider')]
    public function testSubmit(
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
    public static function submitProvider(): iterable
    {
        yield 'submit created dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/submit',
            'payload' => static fn(array $objects) => [
                'ids' => [$objects['answer_dispute_beta_3']->getId()],
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'cannot submit already submitted dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/submit',
            'payload' => static fn(array $objects) => [
                'ids' => [$objects['answer_dispute_alpha_3']->getId()],
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.invalid_status', $body['error']);
            },
        ];

        yield 'submit with nonexistent ids returns nothing to submit' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_dispute']->getId() . '/disputes/submit',
            'payload' => static fn(array $objects) => [
                'ids' => [999999],
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.nothing_to_submit', $body['error']);
            },
        ];
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('deleteProvider')]
    public function testDelete(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('POST', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function deleteProvider(): iterable
    {
        yield 'delete created dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-disputes/' . $objects['answer_dispute_beta_3']->getId() . '/delete',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
            },
        ];

        yield 'cannot delete submitted dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/my/session-disputes/' . $objects['answer_dispute_alpha_3']->getId() . '/delete',
            'expectedStatus' => 422,
            'afterCallback' => static function ($client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('dispute.error.cannot_delete', $body['error']);
            },
        ];
    }
}
