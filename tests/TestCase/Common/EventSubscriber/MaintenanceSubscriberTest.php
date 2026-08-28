<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\EventSubscriber;

use App\Common\Service\MaintenanceService;
use App\Tests\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MaintenanceSubscriberTest extends WebTestCase
{
    use FixturesTrait;

    /**
     * @param list<string> $fixtures
     */
    #[DataProvider('dataProvider')]
    public function testMaintenanceMode(
        array $fixtures,
        bool $maintenanceEnabled,
        ?string $loginAs,
        callable $action,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        $client = static::createClient();
        $objects = self::loadFixtures($fixtures);

        if ($maintenanceEnabled) {
            self::getContainer()->get(MaintenanceService::class)->enable();
        }

        if ($loginAs !== null) {
            $client->loginUser($objects[$loginAs]);
        }

        $action($client, $objects);

        static::assertResponseStatusCodeSame($expectedStatus);
        $afterCallback($client);
    }

    public function testProfilerPathIsNotGatedByMaintenance(): void
    {
        $client = static::createClient();
        self::loadFixtures(['Entity/base.yaml', 'Entity/users.yaml']);
        self::getContainer()->get(MaintenanceService::class)->enable();

        // The profiler firewall disables security, so no token is available and
        // the maintenance gate would otherwise 503. The dev toolbar path must be
        // skipped entirely — the router's own 404 is fine, a 503 is not.
        $client->catchExceptions(true);
        $client->request('GET', '/_wdt/nonexistent');

        // The only thing that matters: the maintenance gate did not intercept
        // this path. The router may still answer 404 for a bogus token.
        static::assertNotSame(503, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function dataProvider(): iterable
    {
        $fixtures = ['Entity/base.yaml', 'Entity/users.yaml'];

        yield 'anonymous user sees maintenance page with login link on home' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => true,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/'),
            'expectedStatus' => 503,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Технічні роботи', $client->getResponse()->getContent());
                static::assertSelectorExists('.error-page a[href="/connect/google"]');
            },
        ];

        yield 'regular player sees maintenance page when enabled' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => true,
            'loginAs' => 'user_player',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 503,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringContainsString('Технічні роботи', $client->getResponse()->getContent());
            },
        ];

        yield 'moderator bypasses maintenance and sees warning banner' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => true,
            'loginAs' => 'user_moderator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                $content = $client->getResponse()->getContent();
                static::assertStringNotContainsString('Технічні роботи', $content);
                static::assertStringContainsString('Ваші зміни можуть бути втрачені', $content);
            },
        ];

        yield 'admin bypasses maintenance via role hierarchy' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => true,
            'loginAs' => 'user_admin',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringNotContainsString('Технічні роботи', $client->getResponse()->getContent());
            },
        ];

        yield 'login route stays reachable during maintenance' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => true,
            'loginAs' => null,
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/connect/google'),
            'expectedStatus' => 302,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringNotContainsString('Технічні роботи', $client->getResponse()->getContent());
            },
        ];

        yield 'player sees no maintenance page when disabled' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => false,
            'loginAs' => 'user_player',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringNotContainsString('Технічні роботи', $client->getResponse()->getContent());
            },
        ];

        yield 'moderator sees no banner when disabled' => [
            'fixtures' => $fixtures,
            'maintenanceEnabled' => false,
            'loginAs' => 'user_moderator',
            'action' => static fn(KernelBrowser $client) => $client->request('GET', '/players'),
            'expectedStatus' => 200,
            'afterCallback' => static function (KernelBrowser $client) {
                static::assertStringNotContainsString('Ваші зміни можуть бути втрачені', $client->getResponse()->getContent());
            },
        ];
    }
}
