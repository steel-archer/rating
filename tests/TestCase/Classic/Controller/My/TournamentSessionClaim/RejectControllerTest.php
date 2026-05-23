<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\TournamentSessionClaim;

use App\Classic\Entity\SessionClaim;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RejectControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/session_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testReject(
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
        yield 'reject by organizer with comment' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'Не підходить'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(SessionClaim::class)
                    ->findOneBy(['session' => $objects['session_pending']->getId()]);
                static::assertSame(SessionClaimStatus::Rejected, $claim->getStatus());
                static::assertSame('Не підходить', $claim->getComment());
                static::assertNotNull($claim->getResolvedAt());
            },
        ];

        yield 'reject by non-organizer' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'reject already rejected' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_rejected']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.not_pending', $body['error']);
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_organizer',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/tournament-claims/' . $objects['session_pending']->getId() . '/reject',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['comment' => 'test'], JSON_THROW_ON_ERROR),
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('reject')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
