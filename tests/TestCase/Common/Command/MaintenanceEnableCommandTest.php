<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Command;

use PHPUnit\Framework\Attributes\DataProvider;

class MaintenanceEnableCommandTest extends AbstractMaintenanceCommandTestCase
{
    #[DataProvider('dataProvider')]
    public function testEnable(bool $enabledBefore): void
    {
        if ($enabledBefore) {
            $this->maintenanceService->enable();
        }

        $tester = $this->runCommand('app:maintenance:enable');

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('Maintenance mode enabled', $tester->getDisplay());
        self::assertTrue($this->maintenanceService->isEnabled());
    }

    /**
     * @return iterable<string, array{enabledBefore: bool}>
     */
    public static function dataProvider(): iterable
    {
        yield 'turns maintenance mode on' => ['enabledBefore' => false];
        yield 'is idempotent when already enabled' => ['enabledBefore' => true];
    }
}
