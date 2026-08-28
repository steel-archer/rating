<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Command;

use PHPUnit\Framework\Attributes\DataProvider;

class MaintenanceDisableCommandTest extends AbstractMaintenanceCommandTestCase
{
    #[DataProvider('dataProvider')]
    public function testDisable(bool $enabledBefore): void
    {
        if ($enabledBefore) {
            $this->maintenanceService->enable();
        }

        $tester = $this->runCommand('app:maintenance:disable');

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('Maintenance mode disabled', $tester->getDisplay());
        self::assertFalse($this->maintenanceService->isEnabled());
    }

    /**
     * @return iterable<string, array{enabledBefore: bool}>
     */
    public static function dataProvider(): iterable
    {
        yield 'turns maintenance mode off' => ['enabledBefore' => true];
        yield 'is idempotent when already disabled' => ['enabledBefore' => false];
    }
}
