<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Mapping\Mapper;
use App\Service\TournamentResultService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/results', name: 'tournament_results', requirements: ['id' => '\d+'], methods: ['GET'])]
class ResultsController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        Request $request,
        TournamentResultService $resultService,
        Mapper $mapper,
    ): Response {
        try {
            $page = PageResolver::resolve($request);

            return $this->render('tournament/_results.html.twig', [
                'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
                'teams' => $resultService->getResults($tournament, $page),
                'page' => $page,
                'lastPage' => $resultService->getLastPageNumber($tournament),
            ]);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
