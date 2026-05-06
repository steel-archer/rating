<?php

namespace App\Controller\PlayerClaim;

use App\DTO\Request\PlayerListRequestDTO;
use App\Repository\PlayerRepository;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player-claim/search', name: 'player_claim_search', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    public function __invoke(
        PlayerRepository $playerRepository,
        TownRepository $townRepository,
        #[MapQueryString] PlayerListRequestDTO $requestDto = new PlayerListRequestDTO(),
    ): Response {
        return $this->render('player_claim/_search_results.html.twig', [
            'players' => $playerRepository->findFreeForList($requestDto),
            'page' => $requestDto->page,
            'lastPage' => $playerRepository->getFreeLastPageNumber($requestDto),
            'filters' => $requestDto->getFilters(),
            'townName' => $requestDto->townId ? $townRepository->find($requestDto->townId)?->getName() : null,
        ]);
    }
}
