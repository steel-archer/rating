<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimited;
use App\DTO\Request\SuggestRequestDTO;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/towns/suggest', name: 'api_towns_suggest', methods: ['GET'])]
#[RateLimited('api_suggest')]
class TownSuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        TownRepository $townRepository,
    ): JsonResponse {
        return $this->json($townRepository->suggest($requestDto->q));
    }
}
