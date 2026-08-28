<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Command;

use PHPUnit\Framework\Attributes\DataProvider;

class MaintenanceStatusCommandTest extends AbstractMaintenanceCommandTestCase
{
    #[DataProvider('dataProvider')]
    public function testStatus(bool $enabled, string $expectedOutput): void
    {
        if ($enabled) {
            $this->maintenanceService->enable();
        }

        $tester = $this->runCommand('app:maintenance:status');

        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString($expectedOutput, $tester->getDisplay());
        self::assertSame($enabled, $this->maintenanceService->isEnabled());
    }

    /**
     * @return iterable<string, array{enabled: bool, expectedOutput: string}>
     */
    public static function dataProvider(): iterable
    {
        yield 'reports enabled' => ['enabled' => true, 'expectedOutput' => 'ENABLED'];
        yield 'reports disabled' => ['enabled' => false, 'expectedOutput' => 'DISABLED'];
    }
}
