<?php

namespace App\Controller\Player;

use App\DTO\Request\PlayerListRequestDTO;
use App\Repository\CountryRepository;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/players/list', name: 'player_list', methods: ['GET'])]
final class ListController extends AbstractController
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
        try {
            return $this->render('player/_list.html.twig', [
                'players' => $this->playerRepository->findForList($requestDto, $this->seasonRepository->findCurrent()),
                'page' => $requestDto->page,
                'lastPage' => $this->playerRepository->getLastPageNumber($requestDto),
                'filters' => $requestDto->getFilters(),
                'townName' => $requestDto->townId ? $this->townRepository->find($requestDto->townId)?->getName() : null,
                'countryName' => $requestDto->countryId ? $this->countryRepository->find($requestDto->countryId)?->getName() : null,
            ]);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
