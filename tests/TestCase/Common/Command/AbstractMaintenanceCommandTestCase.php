<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Command;

use App\Common\Service\MaintenanceService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class AbstractMaintenanceCommandTestCase extends KernelTestCase
{
    protected MaintenanceService $maintenanceService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->maintenanceService = self::getContainer()->get(MaintenanceService::class);
    }

    protected function runCommand(string $name): CommandTester
    {
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find($name));
        $tester->execute([]);

        return $tester;
    }
}
