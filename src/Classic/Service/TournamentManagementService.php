<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Request\Tournament\My\CreateRequestDTO;
use App\Classic\DTO\Request\Tournament\My\EditRequestDTO;
use App\Classic\DTO\Request\Tournament\My\OfficialPlayerDTO;
use App\Classic\Enum\TournamentFormat;
use App\Classic\Enum\TournamentOnlineMode;
use App\Common\Entity\Player;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentOfficial;
use App\Classic\Exception\TournamentValidationException;
use App\Common\Enum\CacheTag;
use App\Classic\Enum\TournamentOfficialRole;
use App\Classic\Enum\TournamentStatus;
use App\Common\Repository\PlayerRepository;
use App\Common\Repository\SeasonRepository;
use App\Common\Service\PlayerFactory;
use App\Classic\Repository\TournamentModerationClaimRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Service\Cache\CacheInvalidator;
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
        private PlayerFactory $playerFactory,
        private SeasonRepository $seasonRepository,
        private TournamentValidator $validator,
        private ClockInterface $clock,
        private CacheInvalidator $cacheInvalidator,
    ) {
    }

    public function create(CreateRequestDTO $dto, Player $player): Tournament
    {
        if ($dto->format === TournamentFormat::Centralized && $dto->onlineMode === TournamentOnlineMode::Mixed) {
            throw new LogicException('tournament.error.centralized_cannot_be_mixed');
        }

        $tournament = new Tournament();
        $tournament->setName($dto->name);
        $tournament->setFormat($dto->format);
        $tournament->setOnlineMode($dto->onlineMode);
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
        // Once a distributed tournament has started, all fields are frozen except
        // the discussion link, which the organizer may still add or update.
        if (
            $tournament->getFormat() === TournamentFormat::Distributed
            && $tournament->getStatus() === TournamentStatus::Published
            && $tournament->getStartedAt() !== null
            && $tournament->getStartedAt() <= DateTimeImmutable::createFromInterface($this->clock->now())
        ) {
            // The discussion link is validated by the DTO (Assert\Url, Assert\Length)
            // before it reaches the service, so no extra check is needed here.
            $tournament->setDiscussionLink($dto->discussionLink);

            $this->em->flush();

            $this->cacheInvalidator->invalidateTournament($tournament);

            return;
        }

        $nameChanged = $tournament->getName() !== $dto->name;

        if ($nameChanged && $tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('tournament.error.cannot_rename_published');
        }

        $errors = $this->validator->validateEdit($dto, $tournament->getFormat());
        if ($errors !== []) {
            throw new TournamentValidationException($errors);
        }

        $startedAt = $dto->startedAt
            ? new DateTimeImmutable($dto->startedAt)->setTime(0, 0)
            : null;
        $endedAt = $dto->endedAt
            ? new DateTimeImmutable($dto->endedAt)->setTime(0, 0)
            : null;

        $tournament->setName($dto->name);
        $tournament->setStartedAt($startedAt);
        $tournament->setEndedAt($endedAt);
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
        $tournament->setQuestionsPerTourMap($this->buildQuestionsPerTourMap($dto));
        $tournament->setDifficulty($dto->difficulty);
        $tournament->setDiscussionLink($dto->discussionLink);
        $tournament->setSeason($endedAt ? $this->seasonRepository->findByDate($endedAt) : null);

        $affectedPlayerIds = $this->syncOfficials($tournament, $dto);

        if ($nameChanged) {
            $tournament->setStatus(TournamentStatus::Draft);
            $this->moderationService->resetModeration($tournament);
        }

        $this->em->flush();

        $this->cacheInvalidator->invalidateTournament($tournament);

        // Players added to or removed from official roles need their personal
        // menu badge refreshed: their old cache is not tagged with this tournament.
        if ($affectedPlayerIds !== []) {
            $this->cacheInvalidator->invalidateTags(
                array_map(static fn(int $playerId) => CacheTag::menuPlayer($playerId), $affectedPlayerIds),
            );
        }
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
            throw new TournamentValidationException($errors);
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
            CacheTag::menuTournament($tournamentId),
        ]);
    }

    /**
     * Synchronises tournament officials with the submitted role lists. Each entry
     * is either an existing player (by id) or a brand new player described inline,
     * which is created on the fly through the shared PlayerFactory.
     *
     * @return list<int> ids of players whose official roles changed (added or removed)
     *
     * @throws LogicException
     */
    private function syncOfficials(Tournament $tournament, EditRequestDTO $dto): array
    {
        $existing = $this->officialRepository->findByTournament($tournament);

        /** @var array<string, list<OfficialPlayerDTO>> $roleMap */
        $roleMap = [
            TournamentOfficialRole::Organizer->value => $dto->organizers,
            TournamentOfficialRole::Editor->value => $dto->editors,
            TournamentOfficialRole::GameJury->value => $dto->gameJury,
            TournamentOfficialRole::AppealJury->value => $dto->appealJury,
        ];

        // Collect, per role, the ids of existing players the organizer kept.
        // New players (no id) do not take part in the removal comparison.
        $keptPlayerIdsByRole = array_map(static function (array $officialDtos) {
            return array_map(
                static fn(OfficialPlayerDTO $officialDto) => $officialDto->id,
                $officialDtos,
            )
                    |> array_filter(...)
                    |> array_values(...);
        }, $roleMap);

        $affectedPlayerIds = [];

        // Remove officials no longer present in the submitted lists. The creator
        // always stays an organizer and is never removed.
        $creatorPlayerId = $tournament->getCreatedBy()?->getId();
        foreach ($existing as $official) {
            $roleValue = $official->getRole()->value;
            if (
                $roleValue === TournamentOfficialRole::Organizer->value
                && $official->getPlayer()->getId() === $creatorPlayerId
            ) {
                continue;
            }
            if (!in_array($official->getPlayer()->getId(), $keptPlayerIdsByRole[$roleValue], true)) {
                $affectedPlayerIds[] = $official->getPlayer()->getId();
                $this->em->remove($official);
            }
        }

        // Force the creator to stay an organizer even if the submitted list omits them.
        if ($creatorPlayerId !== null && !in_array($creatorPlayerId, $keptPlayerIdsByRole[TournamentOfficialRole::Organizer->value], true)) {
            $roleMap[TournamentOfficialRole::Organizer->value][] = new OfficialPlayerDTO(id: $creatorPlayerId);
        }

        $existingKeys = array_map(
            static fn(TournamentOfficial $official) => $official->getRole()->value . '_' . $official->getPlayer()->getId(),
            $existing,
        );

        // Batch-load every existing player referenced by id across all roles.
        $allPlayerIds = [];
        foreach ($roleMap as $officialDtos) {
            foreach ($officialDtos as $officialDto) {
                if ($officialDto->id !== null) {
                    $allPlayerIds[] = $officialDto->id;
                }
            }
        }

        $players = $allPlayerIds !== []
            ? $this->playerRepository->findBy(['id' => array_unique($allPlayerIds)])
            : [];
        $playerIndex = [];
        foreach ($players as $player) {
            $playerIndex[$player->getId()] = $player;
        }

        foreach ($roleMap as $roleValue => $officialDtos) {
            $role = TournamentOfficialRole::from($roleValue);
            $seenPlayerIds = [];
            foreach ($officialDtos as $officialDto) {
                if ($officialDto->id !== null) {
                    // Existing player: skip duplicates and already-assigned pairs.
                    if (in_array($officialDto->id, $seenPlayerIds, true)) {
                        continue;
                    }
                    $seenPlayerIds[] = $officialDto->id;

                    if (in_array($roleValue . '_' . $officialDto->id, $existingKeys, true)) {
                        continue;
                    }

                    $player = $playerIndex[$officialDto->id] ?? null;
                    if ($player === null) {
                        continue;
                    }
                } else {
                    // Brand new player described inline: create and persist it.
                    $player = $this->playerFactory->create(
                        lastName: $officialDto->lastName,
                        firstName: $officialDto->firstName,
                        patronymic: $officialDto->patronymic,
                        townId: $officialDto->townId,
                        townName: $officialDto->townName,
                        countryId: $officialDto->countryId,
                    );
                }

                $official = new TournamentOfficial();
                $official->setTournament($tournament);
                $official->setPlayer($player);
                $official->setRole($role);
                $this->em->persist($official);

                $playerId = $player->getId();
                if ($playerId !== null) {
                    $affectedPlayerIds[] = $playerId;
                }
            }
        }

        $this->em->flush();

        return array_filter(
            $affectedPlayerIds,
            static fn(?int $playerId) => $playerId !== null,
        )
                |> array_unique(...)
                |> array_values(...);
    }

    /**
     * Builds the questionsPerTourMap array from the DTO.
     * In custom mode, uses the provided map; otherwise generates a uniform array.
     *
     * @return list<int>|null
     */
    private function buildQuestionsPerTourMap(EditRequestDTO $dto): ?array
    {
        if ($dto->customQuestionsPerTour && $dto->questionsPerTourMap !== null) {
            return $dto->questionsPerTourMap;
        }

        if ($dto->toursCount === null || $dto->questionsPerTour === null) {
            return null;
        }

        return array_fill(0, $dto->toursCount, $dto->questionsPerTour);
    }
}
