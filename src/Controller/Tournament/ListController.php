<?php

namespace App\Controller\Tournament;

use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournaments/list', name: 'tournament_list')]
final class ListController extends AbstractController
{
    public function __invoke(Request $request, TournamentRepository $tournamentRepository): Response
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));

            return $this->render('tournament/_list.html.twig', [
                'tournaments' => $tournamentRepository->findForList($page),
                'page' => $page,
                'lastPage' => $tournamentRepository->getLastPage(),
            ]);
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
