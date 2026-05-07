<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\Entity\TournamentStatus;
use App\Entity\User;
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

        return $this->render('my/tournament_edit.html.twig', [
            'tournament' => $tournament,
            'claim' => $claimRepository->findByTournament($tournament),
            'officials' => $officialRepository->findByTournament($tournament),
            'publishErrors' => $validator->validatePublish($tournament),
            'readonly' => $readonly,
        ]);
    }
}
