<?php

declare(strict_types=1);

namespace App\Controller\My\Appeal;

use App\DTO\Response\My\AppealDTO;
use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentOfficialRole;
use App\Mapping\Mapper;
use App\Repository\AppealRepository;
use App\Repository\TournamentOfficialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/appeals/{id}', name: 'my_appeals_tournament', requirements: ['id' => '\d+'], methods: ['GET'])]
class TournamentController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        AppealRepository $appealRepository,
        TournamentOfficialRepository $officialRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$officialRepository->hasRole($user->getPlayer(), $tournament, TournamentOfficialRole::AppealJury)) {
            throw $this->createAccessDeniedException();
        }

        $appeals = $appealRepository->findByTournament($tournament);

        return $this->render('my/appeals_tournament.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'appeals' => $mapper->mapMultiple($appeals, AppealDTO::class),
        ]);
    }
}
