<?php

declare(strict_types=1);

namespace App\Common\Controller\PlayerClaim;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\PlayerListRequestDTO;
use App\Common\Repository\PlayerRepository;
use App\Common\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player-claim/search', name: 'player_claim_search', methods: ['GET'])]
#[RateLimited('api_suggest')]
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
