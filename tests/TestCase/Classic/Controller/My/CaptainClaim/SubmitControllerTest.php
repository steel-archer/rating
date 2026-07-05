<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\CaptainClaim;

use App\Classic\Entity\CaptainClaim;
use App\Classic\Enum\CaptainClaimStatus;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubmitControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/captain_claims.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSubmit(
        array $fixtures,
        ?string $loginAs,
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
            '/my/captain-claim',
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
    public static function dataProvider(): iterable
    {
        yield 'success: player not in any team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'comment' => 'Я хочу бути капітаном',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);

                $claim = static::getContainer()->get('doctrine')
                    ->getRepository(CaptainClaim::class)
                    ->findOneBy([
                        'player' => $objects['player_lesya']->getId(),
                        'team' => $objects['team_beta']->getId(),
                        'status' => CaptainClaimStatus::Pending,
                    ]);
                static::assertNotNull($claim);
                static::assertSame('Я хочу бути капітаном', $claim->getComment());
            },
        ];

        yield 'success: player in same team, not captain' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_in_team',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'comment' => 'Передайте мені капітанство',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($json['success']);
            },
        ];

        yield 'error: already captain of this team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_captain',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'comment' => 'Я вже капітан',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.already_captain', $json['error']);
            },
        ];

        yield 'error: player in another team' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_in_team',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'comment' => 'Хочу в іншу команду',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.in_another_team', $json['error']);
            },
        ];

        yield 'error: already has pending claim' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_alpha']->getId(),
                'comment' => 'Перша заявка',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                // Submit second claim — should fail
                $client->request(
                    'POST',
                    '/my/captain-claim',
                    [],
                    [],
                    ['CONTENT_TYPE' => 'application/json'],
                    json_encode([
                        'teamId' => $objects['team_beta']->getId(),
                        'comment' => 'Друга заявка',
                    ], JSON_THROW_ON_ERROR),
                );

                static::assertResponseStatusCodeSame(422);
                $json = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('captain_claim.error.already_has_pending', $json['error']);
            },
        ];

        yield 'error: team not found' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'payload' => static fn() => [
                'teamId' => 999999,
                'comment' => 'Неіснуюча команда',
            ],
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];

        yield 'error: empty comment (validation)' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_cc_player',
            'payload' => static fn(array $objects) => [
                'teamId' => $objects['team_beta']->getId(),
                'comment' => '',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function () {
            },
        ];
    }
}
