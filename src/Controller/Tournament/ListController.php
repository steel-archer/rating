<?php

namespace App\Controller\Tournament;

use App\DTO\Request\TournamentListRequestDTO;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournaments/list', name: 'tournament_list')]
final class ListController extends AbstractController
{
    public function __construct(
        private readonly TournamentRepository $tournamentRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] TournamentListRequestDTO $requestDto = new TournamentListRequestDTO()): Response
    {
        try {
            return $this->render('tournament/_list.html.twig', [
                'tournaments' => $this->tournamentRepository->findForList($requestDto),
                'page' => $requestDto->page,
                'lastPage' => $this->tournamentRepository->getLastPageNumber($requestDto),
                'filters' => $requestDto->getFilters(),
            ]);
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
