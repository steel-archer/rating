<?php

declare(strict_types=1);

namespace App\Common\Controller\Player;

use App\Common\DTO\Request\PlayerListRequestDTO;
use App\Common\Repository\CountryRepository;
use App\Common\Repository\PlayerRepository;
use App\Common\Repository\SeasonRepository;
use App\Common\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/players/list', name: 'player_list', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly SeasonRepository $seasonRepository,
        private readonly TownRepository $townRepository,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] PlayerListRequestDTO $requestDto = new PlayerListRequestDTO()): Response
    {
        return $this->render('player/_list.html.twig', [
            'players' => $this->playerRepository->findForList($requestDto, $this->seasonRepository->findCurrent()),
            'page' => $requestDto->page,
            'lastPage' => $this->playerRepository->getLastPageNumber($requestDto),
            'filters' => $requestDto->getFilters(),
            'townName' => $requestDto->townId ? $this->townRepository->find($requestDto->townId)?->getName() : null,
            'countryName' => $requestDto->countryId ? $this->countryRepository->find($requestDto->countryId)?->getName() : null,
        ]);
    }
}
