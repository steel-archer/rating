<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Dispute;

use App\Classic\DTO\Response\My\DisputeDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentOfficialRole;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
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
