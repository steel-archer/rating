<?php

namespace App\Controller\Api;

use App\DTO\Request\SuggestRequestDTO;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/towns/suggest', name: 'api_towns_suggest', methods: ['GET'])]
final class TownSuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        TownRepository $townRepository,
    ): JsonResponse {
        return $this->json($townRepository->suggest($requestDto->q));
    }
}
