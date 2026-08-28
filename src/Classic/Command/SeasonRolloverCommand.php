<?php

declare(strict_types=1);

namespace App\Classic\Command;

use App\Classic\DTO\Response\Rollover\RolloverResultDTO;
use App\Classic\DTO\Response\Rollover\RolloverTeamResultDTO;
use App\Classic\Service\SeasonRolloverService;
use App\Common\Entity\Season;
use App\Common\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:season:rollover',
    description: 'Create the next season (if missing) and carry over team squads from the finished season',
)]
final class SeasonRolloverCommand extends Command
{
    public function __construct(
        private readonly SeasonRepository $seasonRepository,
        private readonly SeasonRolloverService $rolloverService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Source season id (defaults to the most recent season)',
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would happen without writing any changes',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $source = $this->resolveSourceSeason($input, $io);
        if ($source === null) {
            return Command::FAILURE;
        }

        if ($source->getStartedAt() === null || $source->getEndedAt() === null) {
            $io->error('Source season "' . $source->getName() . '" has no start/end dates.');
            return Command::FAILURE;
        }

        if ($dryRun) {
            try {
                $target = $this->resolveOrCreateTargetSeason($source, $io);
                $result = $this->rolloverService->rollover($source, $target, true);
            } catch (Throwable $ex) {
                $io->error('Rollover failed: ' . $ex->getMessage());
                return Command::FAILURE;
            }

            $this->report($io, $result);

            return Command::SUCCESS;
        }

        $this->entityManager->beginTransaction();
        try {
            $target = $this->resolveOrCreateTargetSeason($source, $io);
            if ($target->getId() === null) {
                $this->entityManager->persist($target);
                // Flush so the new season gets an id before it is used in queries.
                $this->entityManager->flush();
            }
            $result = $this->rolloverService->rollover($source, $target, false);
            $this->entityManager->commit();
        } catch (Throwable $ex) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $io->error('Rollover failed: ' . $ex->getMessage());
            return Command::FAILURE;
        }

        $this->report($io, $result);

        return Command::SUCCESS;
    }

    private function resolveSourceSeason(InputInterface $input, SymfonyStyle $io): ?Season
    {
        $fromOption = $input->getOption('from');

        if ($fromOption !== null) {
            $fromId = filter_var($fromOption, FILTER_VALIDATE_INT);
            if ($fromId === false || $fromId <= 0) {
                $io->error('Option --from must be a positive integer.');
                return null;
            }

            $season = $this->seasonRepository->find($fromId);
            if ($season === null) {
                $io->error('Season with id ' . $fromId . ' not found.');
                return null;
            }

            return $season;
        }

        $season = $this->seasonRepository->findLatest();
        if ($season === null) {
            $io->error('No seasons found. Create at least one season first.');
            return null;
        }

        return $season;
    }

    /**
     * Reuses the existing next season when present, otherwise builds a new one
     * that starts right after the source season and spans a full year. The
     * returned season is not persisted here; the caller decides when to write.
     *
     * @throws LogicException
     */
    private function resolveOrCreateTargetSeason(Season $source, SymfonyStyle $io): Season
    {
        $existing = $this->seasonRepository->findNext($source);
        if ($existing !== null) {
            $io->text('Next season already exists: ' . $existing->getName());

            return $existing;
        }

        $sourceStart = $source->getStartedAt()
            ?? throw new LogicException('Source season has no start date.');

        // Seasons run for a full year without gaps: the next one starts exactly
        // a year after the source and ends one second before the following year.
        $start = $sourceStart->modify('+1 year');
        $end = $start->modify('+1 year')->modify('-1 second');
        $name = $start->format('Y') . '-' . $end->format('Y');

        $target = new Season();
        $target->setName($name);
        $target->setStartedAt($start);
        $target->setEndedAt($end);

        $io->text('Creating next season: ' . $name);

        return $target;
    }

    private function report(SymfonyStyle $io, RolloverResultDTO $result): void
    {
        $io->title(
            ($result->dryRun ? '[DRY RUN] ' : '')
            . 'Season rollover: ' . $result->sourceSeasonName . ' -> ' . $result->targetSeasonName,
        );

        $rows = array_map(
            static fn(RolloverTeamResultDTO $team): array => [
                $team->teamName,
                (string) $team->transferredPlayerCount,
                $team->captainName,
                $team->captaincyReassigned ? 'reassigned' : '-',
            ],
            $result->teams,
        );

        if ($rows !== []) {
            $io->table(['Team', 'Players', 'Captain', 'Captaincy'], $rows);
        }

        $io->definitionList(
            ['Teams transferred' => (string) $result->transferredTeamCount()],
            ['Teams skipped (no eligible players)' => (string) $result->skippedTeamCount],
            ['Players transferred' => (string) $result->transferredPlayerCount()],
            ['Captaincies reassigned' => (string) $result->reassignedCaptaincyCount()],
        );

        if ($result->reassignedCaptaincyCount() > 0) {
            $io->warning(
                'Captaincy was reassigned for ' . $result->reassignedCaptaincyCount()
                . ' team(s) because the previous captain was not carried over.',
            );
        }

        if ($result->dryRun) {
            $io->note('Dry run: no changes were written.');
        } else {
            $io->success('Rollover completed.');
        }
    }
}
