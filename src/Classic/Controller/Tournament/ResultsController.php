<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Common\DTO\Request\PageRequestDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Service\TournamentDetailAccessService;
use App\Classic\Service\TournamentResultService;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/results', name: 'tournament_results', requirements: ['id' => '\d+'], methods: ['GET'])]
class ResultsController extends AbstractController
{
    /**
     * @throws DbalException
     * @throws InvalidArgumentException
     */
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        TournamentResultService $resultService,
        TournamentOfficialRepository $officialRepository,
        TournamentDetailAccessService $detailAccessService,
        Mapper $mapper,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($tournament->areResultsHidden()) {
            $isOfficial = $officialRepository->findOneBy([
                'tournament' => $tournament,
                'player' => $player,
            ]) !== null;

            if (!$isOfficial) {
                return $this->render('tournament/_results_hidden.html.twig', [
                    'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
                    'hiddenUntil' => $tournament->getResultsHiddenUntil(),
                ]);
            }
        }

        return $this->render('tournament/_results.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'teams' => $resultService->getResults($tournament, $dto->page),
            'page' => $dto->page,
            'lastPage' => $resultService->getLastPageNumber($tournament),
            'canViewDetails' => $detailAccessService->canView($tournament, $player),
        ]);
    }
}
