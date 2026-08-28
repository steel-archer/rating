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

#[AsCommand(name: 'app:maintenance:status', description: 'Show whether maintenance mode is currently enabled')]
final class MaintenanceStatusCommand extends Command
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

        if ($this->maintenanceService->isEnabled()) {
            $io->warning('Maintenance mode is ENABLED. The site is accessible to moderators only.');
        } else {
            $io->info('Maintenance mode is DISABLED. The site is accessible to everyone.');
        }

        return Command::SUCCESS;
    }
}
