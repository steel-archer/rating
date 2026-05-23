<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim;

use App\Classic\Entity\SessionClaim;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Service\SessionClaimService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResubmitControllerTest extends WebTestCase
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
    public function testResubmit(
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
        yield 'resubmit rejected claim' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_rejected']->getId() . '/resubmit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(SessionClaim::class)
                    ->findOneBy(['session' => $objects['session_rejected']->getId()]);
                static::assertSame(SessionClaimStatus::Pending, $claim->getStatus());
                static::assertNull($claim->getComment());
                static::assertNull($claim->getResolvedAt());
            },
        ];

        yield 'resubmit non-rejected claim' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_pending']->getId() . '/resubmit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('session_claim.error.not_rejected', $body['error']);
            },
        ];

        yield 'resubmit by non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_other',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_rejected']->getId() . '/resubmit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];

        yield 'throwable returns 500' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_representative',
            'action' => static fn(KernelBrowser $client, array $objects) => $client->request(
                'POST',
                '/my/session-claims/' . $objects['session_rejected']->getId() . '/resubmit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
            ),
            'expectedStatus' => 500,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
                static::assertSame('common.error', $body['error']);
            },
            'mockSetup' => static function (self $test, KernelBrowser $client) {
                $client->disableReboot();
                $stub = $test->createStub(SessionClaimService::class);
                $stub->method('resubmit')->willThrowException(new RuntimeException('unexpected'));
                static::getContainer()->set(SessionClaimService::class, $stub);
            },
        ];
    }
}
