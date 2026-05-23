<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Appeal;

use App\Classic\DTO\Response\My\AppealDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentOfficialRole;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\TournamentOfficialRepository;
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
