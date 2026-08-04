<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Controller\Auth;

use App\Common\Attribute\RateLimited;
use App\Common\Controller\Auth\LogoutController;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogoutControllerTest extends WebTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/users.yaml',
    ];

    /**
     * The `no_limit` policy configured for tests (see rate_limiter.yaml `when@test`) makes it
     * impossible to trigger an actual 429 in an e2e test, so we assert the attribute directly,
     * mirroring how RateLimitSubscriberTest verifies limiter wiring through the attribute.
     */
    public function testControllerIsRateLimited(): void
    {
        $attributes = (new ReflectionClass(LogoutController::class))->getAttributes(RateLimited::class);

        static::assertCount(1, $attributes);
        static::assertSame('auth', $attributes[0]->newInstance()->limiter);
    }

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testLogout(
        array $fixtures,
        ?string $loginAs,
        callable $act,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $act($client);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        yield 'plain visit does not end the session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'act' => static function (KernelBrowser $client) {
                $client->request('GET', '/logout');
            },
            'expectedStatus' => 405,
            'afterCallback' => static function (KernelBrowser $client) {
                $client->request('GET', '/');
                static::assertResponseIsSuccessful();
                static::assertSelectorExists('form.logout-form');
            },
        ];

        yield 'submitting the form ends the session' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'act' => static function (KernelBrowser $client) {
                $crawler = $client->request('GET', '/');
                $client->submit($crawler->filter('form.logout-form')->form());
            },
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client) {
                $client->followRedirect();
                static::assertSelectorNotExists('form.logout-form');
                static::assertSelectorExists('a[href="/connect/google"]');
            },
        ];

        yield 'submitting without the token is refused' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => 'user_with_player',
            'act' => static function (KernelBrowser $client) {
                $client->request('POST', '/logout');
            },
            'expectedStatus' => 403,
            'afterCallback' => static function (KernelBrowser $client) {
                $client->request('GET', '/');
                static::assertResponseIsSuccessful();
                static::assertSelectorExists('form.logout-form');
            },
        ];

        yield 'plain visit is refused for anonymous visitors too' => [
            'fixtures' => self::FIXTURES,
            'loginAs' => null,
            'act' => static function (KernelBrowser $client) {
                $client->request('GET', '/logout');
            },
            'expectedStatus' => 405,
            'afterCallback' => static function (KernelBrowser $client) {
            },
        ];
    }
}
