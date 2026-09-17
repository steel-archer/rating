<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Api;

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
        string|callable $uri,
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

        $resolvedUri = is_callable($uri) ? $uri($objects) : $uri;
        $client->request('GET', $resolvedUri);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client, $objects);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'anonymous gets redirected' => [
            'uri' => '/api/towns/suggest?q=test',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => null,
            'expectedStatus' => 302,
            'afterCallback' => static function ($client, array $objects) {
            },
        ];

        yield 'suggest returns matching towns' => [
            'uri' => '/api/towns/suggest?q=%D0%9A%D0%B8%D1%97',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(1, $data);
                static::assertSame('Київ', $data[0]['name']);
            },
        ];

        yield 'suggest returns multiple matches' => [
            'uri' => '/api/towns/suggest?q=%D0%9B%D1%8C',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertGreaterThanOrEqual(1, count($data));
            },
        ];

        yield 'suggest returns empty for no match' => [
            'uri' => '/api/towns/suggest?q=xyz',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(0, $data);
            },
        ];

        yield 'suggest requires q parameter' => [
            'uri' => '/api/towns/suggest',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 404,
            'afterCallback' => static function ($client, array $objects) {
            },
        ];

        yield 'suggest excludes online pseudo-town' => [
            'uri' => '/api/towns/suggest?q=%D0%9E%D0%BD%D0%BB%D0%B0%D0%B9%D0%BD',
            'fixtures' => ['Entity/base.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(0, $data);
            },
        ];

        yield 'suggest filters towns by matching country' => [
            'uri' => static fn(array $objects) => '/api/towns/suggest?q=%D0%9A%D0%B8%D1%97&countryId='
                . $objects['country_ukraine']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/countries_baltic.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(1, $data);
                static::assertSame('Київ', $data[0]['name']);
            },
        ];

        yield 'suggest returns town of the selected foreign country' => [
            'uri' => static fn(array $objects) => '/api/towns/suggest?q=%D0%92%D1%96%D0%BB&countryId='
                . $objects['country_lithuania']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/countries_baltic.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(1, $data);
                static::assertSame('Вільнюс', $data[0]['name']);
            },
        ];

        yield 'suggest excludes towns of other countries' => [
            // "Вільнюс" belongs to Lithuania, so filtering by Ukraine must not return it.
            'uri' => static fn(array $objects) => '/api/towns/suggest?q=%D0%92%D1%96%D0%BB&countryId='
                . $objects['country_ukraine']->getId(),
            'fixtures' => ['Entity/base.yaml', 'Entity/countries_baltic.yaml', 'Entity/users.yaml'],
            'loginAs' => 'user_regular',
            'expectedStatus' => 200,
            'afterCallback' => static function ($client, array $objects) {
                $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
                static::assertCount(0, $data);
            },
        ];
    }
}
