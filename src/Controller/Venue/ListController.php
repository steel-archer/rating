<?php

namespace App\Controller\Venue;

use App\DTO\Request\VenueListRequestDTO;
use App\Repository\CountryRepository;
use App\Repository\TownRepository;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/venues/list', name: 'venue_list')]
final class ListController extends AbstractController
{
    public function __construct(
        private readonly VenueRepository $venueRepository,
        private readonly TownRepository $townRepository,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] VenueListRequestDTO $requestDto = new VenueListRequestDTO()): Response
    {
        try {
            return $this->render('venue/_list.html.twig', [
                'venues' => $this->venueRepository->findForList($requestDto),
                'page' => $requestDto->page,
                'lastPage' => $this->venueRepository->getLastPageNumber($requestDto),
                'filters' => $requestDto->getFilters(),
                'townName' => $requestDto->townId ? $this->townRepository->find($requestDto->townId)?->getName() : null,
                'countryName' => $requestDto->countryId ? $this->countryRepository->find($requestDto->countryId)?->getName() : null,
            ]);
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
