<?php

declare(strict_types=1);

namespace App\Tests\Controller\PlayerClaim;

use App\Entity\PlayerClaim;
use App\Tests\CsrfTrait;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClaimExistingControllerTest extends WebTestCase
{
    use FixturesTrait;
    use CsrfTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testClaimExisting(
        array $fixtures,
        ?string $loginAs,
        string $searchLastName,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        // search for player via claim search
        $crawler = $client->request('GET', '/player-claim/search?lastName=' . urlencode($searchLastName));

        if ($client->getResponse()->isSuccessful() && $crawler->filter('form[action*="/player-claim/existing"]')->count() > 0) {
            $token = self::extractCsrfToken($crawler, '/player-claim/existing');
            $playerId = $crawler->filter('form[action*="/player-claim/existing"] input[name="playerId"]')->attr('value');
            $client->request('POST', '/player-claim/existing', [
                '_token' => $token,
                'playerId' => $playerId,
            ]);
        }

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'user claims existing free player' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'searchLastName' => 'Українка',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
                $claims = static::getContainer()->get('doctrine')->getRepository(PlayerClaim::class)
                    ->findBy(['user' => $objects['user_regular']]);
                static::assertCount(1, $claims);
                static::assertSame('Українка', $claims[0]->getLastName());
                static::assertNotNull($claims[0]->getPlayer());
                static::assertSame(PlayerClaim::STATUS_PENDING, $claims[0]->getStatus());
            },
        ];

        yield 'anonymous gets redirected' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/tournaments.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'searchLastName' => 'Франко',
            'expectedStatus' => 302,
            'afterCallback' => static function (array $objects) {
            },
        ];
    }
}
