<?php

declare(strict_types=1);

namespace App\Tests\Controller\Tournament\Appeal;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateFormControllerTest extends WebTestCase
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
    public function testCreateForm(
        array $fixtures,
        ?string $loginAs,
        callable $uri,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $client->request('GET', $uri($objects));

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'player who played sees form with question select and type radios' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_player',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertSelectorExists('select[name="questionNumber"]');
                static::assertSelectorExists('input[name="type"][value="remove"]');
                static::assertSelectorExists('input[name="type"][value="accept"]');
                static::assertSelectorExists('textarea[name="text"]');
                // Rejected dispute questions are passed as JSON for JS
                static::assertSelectorExists('#rejected-dispute-questions');
                $json = $client->getCrawler()->filter('#rejected-dispute-questions')->text();
                $questions = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                static::assertContains(2, $questions);
            },
        ];

        yield 'player who did not play gets 403' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_appeal_jury',
            'uri' => static fn(array $objects) => '/tournament/' . $objects['tournament_appeal']->getId() . '/appeals/create',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];
    }
}
