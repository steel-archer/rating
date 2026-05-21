<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\Appeal;

use App\Entity\Appeal;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\AppealStatus;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResolveControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/appeals.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
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
    public static function dataProvider(): iterable
    {
        yield 'jury accepts removal appeal — question removed for all teams' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/my/appeals/resolve/' . $objects['appeal_existing']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
                'verdict' => 'Запитання дійсно некоректне',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);

                $em = static::getContainer()->get('doctrine')->getManager();
                $em->clear();

                $appeal = $em->getRepository(Appeal::class)->find($objects['appeal_existing']->getId());
                static::assertSame(AppealStatus::Accepted, $appeal->getStatus());
                static::assertSame('Запитання дійсно некоректне', $appeal->getVerdict());

                // Question 3 should be removed for both teams
                $answerAlpha = $em->getRepository(TournamentSessionTeamAnswer::class)
                    ->find($objects['answer_appeal_alpha_3']->getId());
                static::assertTrue($answerAlpha->isQuestionRemoved());
                static::assertFalse($answerAlpha->isCorrect());

                $answerBeta = $em->getRepository(TournamentSessionTeamAnswer::class)
                    ->find($objects['answer_appeal_beta_3']->getId());
                static::assertTrue($answerBeta->isQuestionRemoved());
                static::assertFalse($answerBeta->isCorrect());

                // Scores recalculated
                $teamAlpha = $em->getRepository(TournamentSessionTeam::class)
                    ->find($objects['session_team_appeal_alpha']->getId());
                static::assertSame(1, $teamAlpha->getScore());

                $teamBeta = $em->getRepository(TournamentSessionTeam::class)
                    ->find($objects['session_team_appeal_beta']->getId());
                static::assertSame(1, $teamBeta->getScore());
            },
        ];

        yield 'jury rejects appeal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/my/appeals/resolve/' . $objects['appeal_existing']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'reject',
                'verdict' => 'Запитання коректне',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);

                $em = static::getContainer()->get('doctrine')->getManager();
                $em->clear();

                $appeal = $em->getRepository(Appeal::class)->find($objects['appeal_existing']->getId());
                static::assertSame(AppealStatus::Rejected, $appeal->getStatus());
                static::assertSame('Запитання коректне', $appeal->getVerdict());

                // Answers unchanged
                $answerAlpha = $em->getRepository(TournamentSessionTeamAnswer::class)
                    ->find($objects['answer_appeal_alpha_3']->getId());
                static::assertFalse($answerAlpha->isQuestionRemoved());
            },
        ];

        yield 'cannot resolve already resolved appeal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/my/appeals/resolve/' . $objects['appeal_existing']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                // Resolve first time
                static::assertTrue(json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['success']);

                // Try again
                $client->request(
                    'POST',
                    '/my/appeals/resolve/' . $objects['appeal_existing']->getId(),
                    [],
                    [],
                    ['CONTENT_TYPE' => 'application/json'],
                    json_encode(['action' => 'reject'], JSON_THROW_ON_ERROR),
                );
                static::assertResponseStatusCodeSame(422);
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertSame('appeal.error.already_resolved', $body['error']);
            },
        ];

        yield 'non-jury cannot resolve' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/my/appeals/resolve/' . $objects['appeal_existing']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $em = static::getContainer()->get('doctrine')->getManager();
                $em->clear();
                $appeal = $em->getRepository(Appeal::class)->find($objects['appeal_existing']->getId());
                static::assertSame(AppealStatus::Pending, $appeal->getStatus());
            },
        ];

        yield 'jury accepts acceptance appeal — answer marked correct and score recalculated' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/appeals_accept.yaml',
            ],
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/my/appeals/resolve/' . $objects['appeal_accept']->getId(),
            'payload' => static fn(array $objects) => [
                'action' => 'accept',
                'verdict' => 'Відповідь зараховано',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);

                $em = static::getContainer()->get('doctrine')->getManager();
                $em->clear();

                $appeal = $em->getRepository(Appeal::class)->find($objects['appeal_accept']->getId());
                static::assertSame(AppealStatus::Accepted, $appeal->getStatus());

                $answer = $em->getRepository(TournamentSessionTeamAnswer::class)
                    ->find($objects['answer_accept_alpha_2']->getId());
                static::assertTrue($answer->isCorrect());

                $teamAlpha = $em->getRepository(TournamentSessionTeam::class)
                    ->find($objects['session_team_accept_alpha']->getId());
                static::assertSame(2, $teamAlpha->getScore());
            },
        ];
    }
}
