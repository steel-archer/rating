<?php

declare(strict_types=1);

namespace App\Controller\My\Dispute;

use App\DTO\Response\My\DisputeDTO;
use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentOfficialRole;
use App\Mapping\Mapper;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentSessionTeamAnswerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/disputes/{id}', name: 'my_disputes_tournament', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentSessionTeamAnswerRepository $answerRepository,
        TournamentOfficialRepository $officialRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$officialRepository->hasRole($user->getPlayer(), $tournament, TournamentOfficialRole::GameJury)) {
            throw $this->createAccessDeniedException();
        }

        $disputes = $answerRepository->findSubmittedDisputesByTournament($tournament);

        return $this->render('my/disputes_tournament.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'disputes' => $mapper->mapMultiple($disputes, DisputeDTO::class),
        ]);
    }
}
