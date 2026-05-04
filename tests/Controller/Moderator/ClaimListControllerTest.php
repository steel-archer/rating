<?php

declare(strict_types=1);

namespace App\Tests\Controller\Moderator;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ClaimListControllerTest extends WebTestCase
{
    use FixturesTrait;

    #[DataProvider('dataProvider')]
    public function testClaimList(
        string $uri,
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

        $crawler = $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($crawler, $objects);
    }

    public static function dataProvider(): iterable
    {
        yield 'anonymous gets 401' => [
            'uri' => '/moderator/claims',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 401,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];

        yield 'regular user gets 403' => [
            'uri' => '/moderator/claims',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 403,
            'afterCallback' => static function (Crawler $crawler, array $objects) {},
        ];

        yield 'moderator sees pending claims' => [
            'uri' => '/moderator/claims',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_moderator',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertStringContainsString('Франко', $crawler->text());
            },
        ];

        yield 'admin sees claims (role hierarchy)' => [
            'uri' => '/moderator/claims',
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml', 'Entity/claims.yaml'],
            'loginAs' => 'user_admin',
            'expectedStatus' => 200,
            'afterCallback' => static function (Crawler $crawler, array $objects) {
                static::assertStringContainsString('Франко', $crawler->text());
            },
        ];
    }
}
