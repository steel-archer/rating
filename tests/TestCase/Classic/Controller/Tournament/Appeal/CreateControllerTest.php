<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Controller\Tournament\Appeal;

use App\Classic\Entity\Appeal;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Classic\Enum\AppealStatus;
use App\Classic\Enum\AppealType;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateControllerTest extends WebTestCase
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
    public static function dataProvider(): iterable
    {
        yield 'create appeal on removal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 1,
                'type' => 'remove',
                'text' => 'Запитання некоректне, бо...',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['text' => 'Запитання некоректне, бо...']);
                static::assertNotNull($appeal);
                static::assertSame(AppealType::Remove, $appeal->getType());
                static::assertSame(AppealStatus::Pending, $appeal->getStatus());
            },
        ];

        yield 'create appeal on acceptance with rejected dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 2,
                'type' => 'accept',
                'text' => 'Наша відповідь правильна, бо...',
            ],
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertTrue($body['success']);
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['text' => 'Наша відповідь правильна, бо...']);
                static::assertNotNull($appeal);
                static::assertSame(AppealType::Accept, $appeal->getType());
            },
        ];

        yield 'cannot create accept appeal without rejected dispute' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 1,
                'type' => 'accept',
                'text' => 'Спроба без спірної',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['text' => 'Спроба без спірної']);
                static::assertNull($appeal);
            },
        ];

        yield 'cannot create duplicate appeal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 3,
                'type' => 'remove',
                'text' => 'Дублікат',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['text' => 'Дублікат']);
                static::assertNull($appeal);
            },
        ];

        yield 'cannot create appeal with empty text' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 1,
                'type' => 'remove',
                'text' => '   ',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client, array $objects) {
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['tournamentSessionTeamAnswer' => $objects['answer_appeal_alpha_1']->getId()]);
                static::assertNull($appeal);
            },
        ];

        yield 'non-participant cannot create appeal' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 1,
                'type' => 'remove',
                'text' => 'Спроба',
            ],
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'cannot create appeal after deadline' => [
            'fixtures' => [
                'Entity/base.yaml',
                'Entity/appeals_expired.yaml',
            ],
            'loginAs' => 'user_appeal_expired_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal_expired']->getId() . '/appeals/create',
            'payload' => static fn(array $objects) => [
                'questionNumber' => 1,
                'type' => 'remove',
                'text' => 'Запізнілась',
            ],
            'expectedStatus' => 422,
            'afterCallback' => static function (KernelBrowser $client) {
                $appeal = static::getContainer()->get('doctrine')
                    ->getRepository(Appeal::class)
                    ->findOneBy(['text' => 'Запізнілась']);
                static::assertNull($appeal);
            },
        ];
    }
}
