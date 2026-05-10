<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimited;
use App\DTO\Request\SuggestRequestDTO;
use App\Repository\CountryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/countries/suggest', name: 'api_countries_suggest', methods: ['GET'])]
#[RateLimited('api_suggest')]
class CountrySuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        CountryRepository $countryRepository,
    ): JsonResponse {
        return $this->json($countryRepository->suggest($requestDto->q));
    }
}
