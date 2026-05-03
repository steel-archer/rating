<?php

namespace App\Controller\Tournament;

use App\Repository\TournamentRepository;
use App\Service\TournamentResultService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Helper\PageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/results', name: 'tournament_results', requirements: ['id' => '\d+'])]
final class ResultsController extends AbstractController
{
    public function __invoke(int $id, Request $request, TournamentRepository $tournamentRepository, TournamentResultService $resultService): Response
    {
        try {
            $tournament = $tournamentRepository->findWithSeason($id)
                ?? throw new NotFoundHttpException("Tournament #$id not found");

            $page = PageResolver::resolve($request);

            return $this->render('tournament/_results.html.twig', [
                'tournament' => $tournament,
                'teams' => $resultService->getResults($tournament, $page),
                'page' => $page,
                'lastPage' => $resultService->getLastPageNumber($tournament),
            ]);
        } catch (NotFoundHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
