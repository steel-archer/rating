<?php

declare(strict_types=1);

namespace App\Tests\Controller\My\SessionClaim;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsTemplateControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/results.yaml',
    ];

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testTemplate(
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
        yield 'download template for owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/template',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client) {
                static::assertResponseHeaderSame(
                    'content-type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                );
            },
        ];

        yield 'access denied for non-owner' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_results_other',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/template',
            'expectedStatus' => 403,
            'afterCallback' => static function () {
            },
        ];

        yield 'redirect for anonymous' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results']->getId() . '/results/template',
            'expectedStatus' => 302,
            'afterCallback' => static function () {
            },
        ];

        yield 'not found when session has no teams' => [
            'fixtures' => ['Entity/base.yaml', 'Entity/results_empty.yaml'],
            'loginAs' => 'user_results_empty_rep',
            'uri' => static fn(array $objects) => '/my/session-claims/' . $objects['session_results_empty']->getId() . '/results/template',
            'expectedStatus' => 404,
            'afterCallback' => static function () {
            },
        ];
    }
}
