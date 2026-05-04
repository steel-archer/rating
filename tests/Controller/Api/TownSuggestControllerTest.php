<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TownSuggestControllerTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testSuggest(
        string $uri,
        array $fixtures,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        $client->request('GET', $uri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'suggest returns matching towns' => [
            'uri' => '/api/towns/suggest?q=%D0%9A%D0%B8%D1%97',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertCount(1, $data);
                static::assertSame('Київ', $data[0]['name']);
            },
        ];

        yield 'suggest returns multiple matches' => [
            'uri' => '/api/towns/suggest?q=%D0%9B%D1%8C',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertGreaterThanOrEqual(1, count($data));
            },
        ];

        yield 'suggest returns empty for no match' => [
            'uri' => '/api/towns/suggest?q=xyz',
            'fixtures' => ['Entity/base.yaml'],
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true);
                static::assertCount(0, $data);
            },
        ];

        yield 'suggest requires q parameter' => [
            'uri' => '/api/towns/suggest',
            'fixtures' => [],
            'expectedStatus' => 404,
            'afterCallback' => static function ($client, array $objects) {
            },
        ];
    }
}
