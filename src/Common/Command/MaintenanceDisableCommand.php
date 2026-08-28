<?php

declare(strict_types=1);

namespace App\Common\Command;

use App\Common\Service\MaintenanceService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:maintenance:disable', description: 'Disable maintenance mode (restore normal access)')]
final class MaintenanceDisableCommand extends Command
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->maintenanceService->disable();

        $io->success('Maintenance mode disabled. The site is now accessible to everyone.');

        return Command::SUCCESS;
    }
}
