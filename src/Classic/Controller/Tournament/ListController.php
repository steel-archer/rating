<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Request\TournamentListRequestDTO;
use App\Classic\Service\TournamentService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournaments/list', name: 'tournament_list', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __construct(
        private readonly TournamentService $tournamentService,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(#[MapQueryString] TournamentListRequestDTO $requestDto = new TournamentListRequestDTO()): Response
    {
        $result = $this->tournamentService->getList($requestDto);

        return $this->render('tournament/_list.html.twig', [
            'tournaments' => $result['tournaments'],
            'page' => $requestDto->page,
            'lastPage' => $result['lastPage'],
            'filters' => $requestDto->getFilters(),
        ]);
    }
}
