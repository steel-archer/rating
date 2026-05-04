<?php

declare(strict_types=1);

namespace App\Tests\Controller\Claim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubmittedControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSubmitted(
        string $uri,
        array $fixtures,
        ?string $loginAs,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'uri' => '/claim/submitted',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
        ];

        yield 'logged in user sees submitted page' => [
            'uri' => '/claim/submitted',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
        ];
    }
}
