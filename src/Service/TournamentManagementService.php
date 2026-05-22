<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\Tournament\My\CreateRequestDTO;
use App\DTO\Request\Tournament\My\EditRequestDTO;
use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Enum\CacheTag;
use App\Enum\TournamentOfficialRole;
use App\Enum\TournamentStatus;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentOfficialRepository;
use App\Service\Cache\CacheInvalidator;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Psr\Clock\ClockInterface;

class TournamentManagementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournamentModerationService $moderationService,
        private TournamentDocumentService $documentService,
        private TournamentModerationClaimRepository $claimRepository,
        private TournamentOfficialRepository $officialRepository,
        private PlayerRepository $playerRepository,
        private SeasonRepository $seasonRepository,
        private TournamentValidator $validator,
        private ClockInterface $clock,
        private CacheInvalidator $cacheInvalidator,
    ) {
    }

    public function create(CreateRequestDTO $dto, Player $player): Tournament
    {
        $tournament = new Tournament();
        $tournament->setName($dto->name);
        $tournament->setCreatedBy($player);

        $this->em->persist($tournament);

        $official = new TournamentOfficial();
        $official->setTournament($tournament);
        $official->setPlayer($player);
        $official->setRole(TournamentOfficialRole::Organizer);

        $this->em->persist($official);
        $this->em->flush();

        return $tournament;
    }

    /**
     * @throws DateMalformedStringException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function update(Tournament $tournament, EditRequestDTO $dto): void
    {
        if (
            $tournament->getStatus() === TournamentStatus::Published
            && $tournament->getStartedAt() !== null
            && $tournament->getStartedAt() <= DateTimeImmutable::createFromInterface($this->clock->now())
        ) {
            throw new LogicException('tournament.error.cannot_edit_started');
        }

        $errors = $this->validator->validateEdit($dto);
        if ($errors !== []) {
            throw new LogicException(implode(' ', $errors));
        }

        $nameChanged = $tournament->getName() !== $dto->name;

        $startedAt = $dto->startedAt
            ? new DateTimeImmutable($dto->startedAt)->setTime(0, 0)
            : null;

        $tournament->setName($dto->name);
        $tournament->setStartedAt($startedAt);
        $tournament->setEndedAt(
            $dto->endedAt
                ? new DateTimeImmutable($dto->endedAt)->setTime(0, 0)
                : null,
        );
        $tournament->setResultsHiddenUntil(
            $dto->resultsHiddenUntil
                ? new DateTimeImmutable($dto->resultsHiddenUntil)->setTime(0, 0)
                : null,
        );
        $tournament->setRegistrationDeadline(
            $dto->registrationDeadline
                ? new DateTimeImmutable($dto->registrationDeadline)->setTime(0, 0)
                : null,
        );
        $tournament->setDetailsHiddenUntil(
            $dto->detailsHiddenUntil
                ? new DateTimeImmutable($dto->detailsHiddenUntil)->setTime(0, 0)
                : null,
        );
        $tournament->setSubmissionDeadline(
            $dto->submissionDeadline
                ? new DateTimeImmutable($dto->submissionDeadline)->setTime(0, 0)
                : null,
        );
        $tournament->setAppealDeadline(
            $dto->appealDeadline
                ? new DateTimeImmutable($dto->appealDeadline)->setTime(0, 0)
                : null,
        );
        $tournament->setToursCount($dto->toursCount);
        $tournament->setQuestionsPerTour($dto->questionsPerTour);
        $tournament->setDifficulty($dto->difficulty);
        $tournament->setSeason($startedAt ? $this->seasonRepository->findByDate($startedAt) : null);

        $this->syncOfficials($tournament, $dto);

        if ($nameChanged) {
            $tournament->setStatus(TournamentStatus::Draft);
            $this->moderationService->resetModeration($tournament);
        }

        $this->em->flush();

        $this->cacheInvalidator->invalidateTournament($tournament);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function publish(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('Tournament is already published');
        }

        $errors = $this->validator->validatePublish($tournament);
        if ($errors !== []) {
            throw new LogicException(implode(' ', $errors));
        }

        $tournament->setStatus(TournamentStatus::Published);
        $this->em->flush();

        $this->cacheInvalidator->invalidateTournamentWithParticipants($tournament);
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function delete(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('tournament.error.cannot_delete_published');
        }

        $tournamentId = $tournament->getId();

        $this->documentService->deleteAllByTournament($tournament);

        $claim = $this->claimRepository->findByTournament($tournament);
        if ($claim !== null) {
            $this->em->remove($claim);
        }

        $officials = $this->officialRepository->findByTournament($tournament);
        foreach ($officials as $official) {
            $this->em->remove($official);
        }

        $this->em->remove($tournament);
        $this->em->flush();

        $this->cacheInvalidator->invalidateTags([
            CacheTag::tournament($tournamentId),
            CacheTag::TournamentList->value,
        ]);
    }

    private function syncOfficials(Tournament $tournament, EditRequestDTO $dto): void
    {
        $existing = $this->officialRepository->findByTournament($tournament);

        $roleMap = [
            TournamentOfficialRole::Organizer->value => $dto->organizers,
            TournamentOfficialRole::Editor->value => $dto->editors,
            TournamentOfficialRole::GameJury->value => $dto->gameJury,
            TournamentOfficialRole::AppealJury->value => $dto->appealJury,
        ];

        $creatorPlayerId = $tournament->getCreatedBy()?->getId();
        foreach ($existing as $official) {
            $roleValue = $official->getRole()->value;
            $playerIds = $roleMap[$roleValue];
            if (
                $roleValue === TournamentOfficialRole::Organizer->value
                && $official->getPlayer()->getId() === $creatorPlayerId
            ) {
                continue;
            }
            if (!in_array($official->getPlayer()->getId(), $playerIds, true)) {
                $this->em->remove($official);
            }
        }

        $organizerIds = $roleMap[TournamentOfficialRole::Organizer->value];
        if ($creatorPlayerId !== null && !in_array($creatorPlayerId, $organizerIds, true)) {
            $roleMap[TournamentOfficialRole::Organizer->value][] = $creatorPlayerId;
        }

        $existingKeys = array_map(
            static fn(TournamentOfficial $official) => $official->getRole()->value . '_' . $official->getPlayer()->getId(),
            $existing,
        );

        $allNewPlayerIds = [];
        foreach ($roleMap as $roleValue => $playerIds) {
            foreach (array_unique($playerIds) as $playerId) {
                if (!in_array($roleValue . '_' . $playerId, $existingKeys, true)) {
                    $allNewPlayerIds[] = $playerId;
                }
            }
        }

        $players = $allNewPlayerIds !== []
            ? $this->playerRepository->findBy(['id' => array_unique($allNewPlayerIds)])
            : [];
        $playerIndex = [];
        foreach ($players as $player) {
            $playerIndex[$player->getId()] = $player;
        }

        foreach ($roleMap as $roleValue => $playerIds) {
            $role = TournamentOfficialRole::from($roleValue);
            foreach (array_unique($playerIds) as $playerId) {
                if (in_array($roleValue . '_' . $playerId, $existingKeys, true)) {
                    continue;
                }
                $player = $playerIndex[$playerId] ?? null;
                if ($player === null) {
                    continue;
                }
                $official = new TournamentOfficial();
                $official->setTournament($tournament);
                $official->setPlayer($player);
                $official->setRole($role);
                $this->em->persist($official);
            }
        }
    }
}
