<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Api;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MenuCountsControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = ['Entity/base.yaml', 'Entity/menu_counts.yaml'];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testMenuCounts(
        array $fixtures,
        ?string $loginAs,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', '/api/menu-counts');

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function (): void {
            },
        ];

        yield 'moderator gets shared moderation counts and no personal block' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_mod',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client): void {
                $data = self::decode($client);

                static::assertNull($data['my']);
                static::assertSame(5, $data['moderation']['total']);
                static::assertSame(2, $data['moderation']['playerClaims']);
                static::assertSame(1, $data['moderation']['tournaments']);
                static::assertSame(1, $data['moderation']['captainClaims']);
                static::assertSame(1, $data['moderation']['venues']);
            },
        ];

        yield 'organizer and jury gets personal counts and no moderation block' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_franko',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client): void {
                $data = self::decode($client);

                static::assertNull($data['moderation']);
                static::assertSame(5, $data['my']['total']);
                static::assertSame(3, $data['my']['organizerClaims']);
                static::assertSame(1, $data['my']['disputes']);
                static::assertSame(1, $data['my']['appeals']);
            },
        ];

        yield 'co-organizer sees only their own tournaments' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_lesya',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client): void {
                $data = self::decode($client);

                static::assertNull($data['moderation']);
                static::assertSame(2, $data['my']['total']);
                static::assertSame(2, $data['my']['organizerClaims']);
                static::assertSame(0, $data['my']['disputes']);
                static::assertSame(0, $data['my']['appeals']);
            },
        ];

        yield 'player without roles gets zeroed personal counts' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_menu_plain',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client): void {
                $data = self::decode($client);

                static::assertNull($data['moderation']);
                static::assertSame(0, $data['my']['total']);
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(object $client): array
    {
        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
