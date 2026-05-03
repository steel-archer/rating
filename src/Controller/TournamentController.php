<?php

namespace App\Controller;

use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament')]
final class TournamentController extends AbstractController
{
    #[Route('/{id}', name: 'tournament_show', requirements: ['id' => '\d+'])]
    public function show(int $id, TournamentService $tournamentService): Response
    {
        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournamentService->get($id),
        ]);
    }
}
