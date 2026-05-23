<?php

declare(strict_types=1);

namespace App\Common\Command;

use App\Common\Enum\PlayerClaimStatus;
use App\Common\Repository\PlayerClaimRepository;
use App\Common\Repository\UserRepository;
use App\Common\Service\PlayerClaimService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:promote-admin', description: 'Approve a pending claim and grant ROLE_ADMIN to a user')]
final class PromoteAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PlayerClaimRepository $claimRepository,
        private readonly PlayerClaimService $claimService,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            $io->error("User with email \"$email\" not found. Log in via Google first.");
            return Command::FAILURE;
        }

        $claim = $this->claimRepository->findOneBy(['user' => $user, 'status' => PlayerClaimStatus::Pending]);
        if ($claim !== null) {
            $this->claimService->approve($claim->getId());
            $io->info('Pending claim approved.');
        } elseif ($user->getPlayer() === null) {
            $io->error('No pending claim found. Submit a player claim on the site first.');
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
        }
        $this->em->flush();

        $io->success("User \"$email\" is now an admin.");

        return Command::SUCCESS;
    }
}
