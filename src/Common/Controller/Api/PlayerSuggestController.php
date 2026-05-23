<?php

declare(strict_types=1);

namespace App\Common\Controller\Api;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\SuggestRequestDTO;
use App\Common\Repository\PlayerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players/suggest', name: 'api_players_suggest', methods: ['GET'])]
#[RateLimited('api_suggest')]
class PlayerSuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        PlayerRepository $playerRepository,
    ): JsonResponse {
        return $this->json($playerRepository->suggest($requestDto->q));
    }
}
