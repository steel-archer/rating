<?php

namespace App\Controller\Tournament;

use App\Exception\EntityNotFoundException;
use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}', name: 'tournament_show', requirements: ['id' => '\d+'], methods: ['GET'])]
final class ShowController extends AbstractController
{
    public function __invoke(int $id, TournamentService $tournamentService): Response
    {
        try {
            $tournament = $tournamentService->get($id);
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (Throwable $exception) { // @codeCoverageIgnoreStart
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception); // @codeCoverageIgnoreEnd
        }

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
        ]);
    }
}
