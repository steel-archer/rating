<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\DTO\Response\My\ModerationClaimDTO;
use App\DTO\Response\My\TournamentEditDTO;
use App\DTO\Response\My\TournamentOfficialDTO;
use App\Entity\User;
use App\Enum\TournamentStatus;
use App\Mapping\Mapper;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentRepository;
use App\Security\TournamentOwnerVoter;
use App\Service\TournamentValidator;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/{id}/edit', name: 'my_tournament_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class EditController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentRepository $tournamentRepository,
        TournamentModerationClaimRepository $claimRepository,
        TournamentOfficialRepository $officialRepository,
        TournamentValidator $validator,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $tournament = $tournamentRepository->find($id);

        if ($tournament === null || !$this->isGranted(TournamentOwnerVoter::EDIT, $tournament)) {
            throw $this->createNotFoundException();
        }

        $readonly = $tournament->getStatus() === TournamentStatus::Published
            && $tournament->getStartedAt() !== null
            && $tournament->getStartedAt() <= new DateTimeImmutable();

        $moderationClaim = $claimRepository->findByTournament($tournament);
        $claimDto = $moderationClaim !== null
            ? $mapper->map($moderationClaim, ModerationClaimDTO::class)
            : null;

        $officials = $mapper->mapMultiple(
            $officialRepository->findByTournament($tournament),
            TournamentOfficialDTO::class,
        );

        return $this->render('my/tournament_edit.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentEditDTO::class),
            'claim' => $claimDto,
            'officials' => $officials,
            'publishErrors' => $validator->validatePublish($tournament),
            'readonly' => $readonly,
        ]);
    }
}
