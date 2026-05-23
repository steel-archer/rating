<?php

declare(strict_types=1);

namespace App\Common\Controller\Venue;

use App\Common\DTO\Request\VenueListRequestDTO;
use App\Common\Repository\CountryRepository;
use App\Common\Repository\TownRepository;
use App\Common\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues/list', name: 'venue_list', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __construct(
        private readonly VenueRepository $venueRepository,
        private readonly TownRepository $townRepository,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] VenueListRequestDTO $requestDto = new VenueListRequestDTO()): Response
    {
        return $this->render('venue/_list.html.twig', [
            'venues' => $this->venueRepository->findForList($requestDto),
            'page' => $requestDto->page,
            'lastPage' => $this->venueRepository->getLastPageNumber($requestDto),
            'filters' => $requestDto->getFilters(),
            'townName' => $requestDto->townId ? $this->townRepository->find($requestDto->townId)?->getName() : null,
            'countryName' => $requestDto->countryId ? $this->countryRepository->find($requestDto->countryId)?->getName() : null,
        ]);
    }
}
