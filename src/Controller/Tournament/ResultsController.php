<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Request\PageRequestDTO;
use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentOfficialRepository;
use App\Service\TournamentResultService;
use Doctrine\DBAL\Exception;
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
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        TournamentResultService $resultService,
        TournamentOfficialRepository $officialRepository,
        Mapper $mapper,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        if ($tournament->areResultsHidden()) {
            /** @var User $user */
            $user = $this->getUser();

            $isOfficial = $officialRepository->findOneBy([
                'tournament' => $tournament,
                'player' => $user->getPlayer(),
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
        ]);
    }
}
