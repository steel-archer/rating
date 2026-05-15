<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DisputesControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/disputes.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testDisputes(
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
    public static function dataProvider(): iterable
    {
        yield 'official can view disputes' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_jury',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_dispute']->getId() . '/disputes',
            'expectedStatus' => 200,
        ];

        yield 'organizer can view disputes' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_rep',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_dispute']->getId() . '/disputes',
            'expectedStatus' => 200,
        ];

        yield 'non-official can view published tournament disputes' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_dispute_other',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_dispute']->getId() . '/disputes',
            'expectedStatus' => 200,
        ];
    }
}
