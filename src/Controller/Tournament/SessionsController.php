<?php

namespace App\Controller\Tournament;

use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/sessions', name: 'tournament_sessions', requirements: ['id' => '\d+'], methods: ['GET'])]
final class SessionsController extends AbstractController
{
    public function __invoke(int $id, TournamentRepository $tournamentRepository): Response
    {
        try {
            $tournament = $tournamentRepository->find($id)
                ?? throw new NotFoundHttpException("Tournament #$id not found");

            return $this->render('tournament/sessions.html.twig', [
                'tournament' => $tournament,
            ]);
        } catch (NotFoundHttpException $ex) { // @codeCoverageIgnoreStart
            throw $ex; // @codeCoverageIgnoreEnd
        } catch (Throwable $ex) { // @codeCoverageIgnoreStart
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex); // @codeCoverageIgnoreEnd
        }
    }
}
