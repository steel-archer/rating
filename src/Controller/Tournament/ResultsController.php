<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentOfficialRepository;
use App\Service\TournamentResultService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Enum\TournamentOfficialRole;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/results', name: 'tournament_results', requirements: ['id' => '\d+'], methods: ['GET'])]
class ResultsController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        Request $request,
        TournamentResultService $resultService,
        TournamentOfficialRepository $officialRepository,
        Mapper $mapper,
    ): Response {
        if ($tournament->areResultsHidden()) {
            /** @var User|null $user */
            $user = $this->getUser();
            $player = $user?->getPlayer();

            $isOrganizer = $player !== null
                && $officialRepository->hasRole($player, $tournament, TournamentOfficialRole::Organizer);

            if (!$isOrganizer) {
                return $this->render('tournament/_results_hidden.html.twig', [
                    'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
                    'hiddenUntil' => $tournament->getResultsHiddenUntil(),
                ]);
            }
        }

        $page = PageResolver::resolve($request);

        return $this->render('tournament/_results.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'teams' => $resultService->getResults($tournament, $page),
            'page' => $page,
            'lastPage' => $resultService->getLastPageNumber($tournament),
        ]);
    }
}
