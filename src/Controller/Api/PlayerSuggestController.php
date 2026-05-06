<?php

namespace App\Controller\Api;

use App\DTO\Request\SuggestRequestDTO;
use App\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/suggest', name: 'api_players_suggest', methods: ['GET'])]
class PlayerSuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        PlayerRepository $playerRepository,
    ): JsonResponse {
        return $this->json($playerRepository->suggest($requestDto->q));
    }
}
