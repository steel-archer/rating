<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\My\SessionClaim\Squad;

use App\Classic\Entity\TournamentSession;
use App\Classic\Service\SessionResultService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SquadDeleteControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/squad.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testDeleteSquad(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
        ?callable $beforeRequest = null,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        if ($beforeRequest !== null) {
            $beforeRequest($objects);
        }

        $client->request(
            'POST',
            $uri($objects),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'delete squad successfully' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/delete',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_other',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/delete',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'delete squad with answers' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/squad.yaml',
                'Entity/squad_with_answers.yaml',
            ],
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/delete',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertTrue($data['success']);
            },
        ];

        yield 'delete squad invalidates cached session results' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_squad_rep',
            'uri' => static fn(array $objects) => '/my/session-teams/' . $objects['session_team_existing']->getId() . '/delete',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $em = static::getContainer()->get('doctrine')->getManager();
                $session = $em->find(TournamentSession::class, $objects['session_squad_approved']->getId());

                $results = static::getContainer()->get(SessionResultService::class)->getSessionResults($session);
                static::assertCount(0, $results);
            },
            'beforeRequest' => static function (array $objects) {
                $results = static::getContainer()
                    ->get(SessionResultService::class)
                    ->getSessionResults($objects['session_squad_approved']);

                static::assertCount(1, $results, 'Precondition: session should have one team before it is deleted.');
            },
        ];
    }
}
