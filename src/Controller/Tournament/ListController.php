<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Request\TournamentListRequestDTO;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournaments/list', name: 'tournament_list', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __construct(
        private readonly TournamentRepository $tournamentRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] TournamentListRequestDTO $requestDto = new TournamentListRequestDTO()): Response
    {
        return $this->render('tournament/_list.html.twig', [
            'tournaments' => $this->tournamentRepository->findForList($requestDto),
            'page' => $requestDto->page,
            'lastPage' => $this->tournamentRepository->getLastPageNumber($requestDto),
            'filters' => $requestDto->getFilters(),
        ]);
    }
}
