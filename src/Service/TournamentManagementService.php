<?php

namespace App\Service;

use App\DTO\Request\Tournament\My\EditRequestDTO;
use App\Entity\Tournament;
use App\Entity\TournamentModerationClaim;
use App\Entity\TournamentModerationStatus;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentOfficialRole;
use App\Entity\TournamentStatus;
use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentOfficialRepository;
use DateMalformedStringException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class TournamentManagementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TournamentModerationClaimRepository $claimRepository,
        private TournamentOfficialRepository $officialRepository,
        private PlayerRepository $playerRepository,
        private SeasonRepository $seasonRepository,
        private TournamentValidator $validator,
    ) {
    }

    public function create(string $name, User $user): Tournament
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setCreatedBy($user);

        $this->em->persist($tournament);

        $official = new TournamentOfficial();
        $official->setTournament($tournament);
        $official->setPlayer($user->getPlayer());
        $official->setRole(TournamentOfficialRole::Organizer);

        $this->em->persist($official);
        $this->em->flush();

        return $tournament;
    }

    /**
     * @throws DateMalformedStringException
     * @throws LogicException
     */
    public function update(Tournament $tournament, EditRequestDTO $dto): void
    {
        if (
            $tournament->getStatus() === TournamentStatus::Published
            && $tournament->getStartedAt() !== null
            && $tournament->getStartedAt() <= new DateTime()
        ) {
            throw new LogicException('tournament.error.cannot_edit_started');
        }

        $errors = $this->validator->validateEdit($dto);
        if ($errors !== []) {
            throw new LogicException(implode(' ', $errors));
        }

        $nameChanged = $tournament->getName() !== $dto->name;

        $startedAt = $dto->startedAt ? new DateTime($dto->startedAt) : null;

        $tournament->setName($dto->name);
        $tournament->setStartedAt($startedAt);
        $tournament->setEndedAt($dto->endedAt ? new DateTime($dto->endedAt) : null);
        $tournament->setToursCount($dto->toursCount);
        $tournament->setQuestionsPerTour($dto->questionsPerTour);
        $tournament->setDifficulty($dto->difficulty);
        $tournament->setSeason($startedAt ? $this->seasonRepository->findByDate($startedAt) : null);

        $this->syncOfficials($tournament, $dto);

        if ($nameChanged) {
            $tournament->setStatus(TournamentStatus::Draft);
            $this->resetModeration($tournament);
        }

        $tournament->setUpdatedAt(new DateTime());
        $this->em->flush();
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

        // Remove officials no longer in DTO (except creator as organizer)
        $creatorPlayerId = $tournament->getCreatedBy()?->getPlayer()?->getId();
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

        // Ensure creator is always among organizers
        $organizerIds = $roleMap[TournamentOfficialRole::Organizer->value];
        if ($creatorPlayerId !== null && !in_array($creatorPlayerId, $organizerIds, true)) {
            $roleMap[TournamentOfficialRole::Organizer->value][] = $creatorPlayerId;
        }

        // Add new ones that don't exist yet
        $existingKeys = array_map(
            static fn(TournamentOfficial $official) => $official->getRole()->value . '_' . $official->getPlayer()->getId(),
            $existing,
        );

        foreach ($roleMap as $roleValue => $playerIds) {
            $role = TournamentOfficialRole::from($roleValue);
            foreach (array_unique($playerIds) as $playerId) {
                if (in_array($roleValue . '_' . $playerId, $existingKeys, true)) {
                    continue;
                }
                $player = $this->playerRepository->find($playerId);
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

    public function submitForModeration(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('Cannot submit published tournament');
        }

        $claim = $this->claimRepository->findByTournament($tournament);

        if ($claim !== null && $claim->getStatus() === TournamentModerationStatus::Approved) {
            throw new LogicException('Tournament is already approved');
        }

        $this->resetModeration($tournament);
    }

    private function resetModeration(Tournament $tournament): void
    {
        $claim = $this->claimRepository->findByTournament($tournament);

        if ($claim === null) {
            $claim = new TournamentModerationClaim();
            $claim->setTournament($tournament);
            $this->em->persist($claim);
        } else {
            $claim->setStatus(TournamentModerationStatus::Pending);
            $claim->setComment(null);
            $claim->setCreatedAt(new DateTime());
            $claim->setResolvedAt(null);
        }

        $this->em->flush();
    }

    public function approve(Tournament $tournament): void
    {
        $claim = $this->claimRepository->findByTournament($tournament)
            ?? throw new LogicException('No moderation claim found');

        if ($claim->getStatus() !== TournamentModerationStatus::Pending) {
            throw new LogicException('Claim is not pending');
        }

        $claim->setStatus(TournamentModerationStatus::Approved);
        $claim->setResolvedAt(new DateTime());

        $this->em->flush();
    }

    public function reject(Tournament $tournament, ?string $comment): void
    {
        $claim = $this->claimRepository->findByTournament($tournament)
            ?? throw new LogicException('No moderation claim found');

        if ($claim->getStatus() !== TournamentModerationStatus::Pending) {
            throw new LogicException('Claim is not pending');
        }

        $claim->setStatus(TournamentModerationStatus::Rejected);
        $claim->setComment($comment);
        $claim->setResolvedAt(new DateTime());

        $this->em->flush();
    }

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
    }

    public function delete(Tournament $tournament): void
    {
        if ($tournament->getStatus() === TournamentStatus::Published) {
            throw new LogicException('tournament.error.cannot_delete_published');
        }

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
    }
}
