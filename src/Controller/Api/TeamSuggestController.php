<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Request\SuggestRequestDTO;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/teams/suggest', name: 'api_teams_suggest', methods: ['GET'])]
class TeamSuggestController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] SuggestRequestDTO $requestDto,
        TeamRepository $teamRepository,
    ): JsonResponse {
        return $this->json($teamRepository->suggest($requestDto->q));
    }
}
