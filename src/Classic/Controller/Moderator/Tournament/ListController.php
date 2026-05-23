<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\Tournament;

use App\Classic\DTO\Request\Tournament\Moderation\ListRequestDTO;
use App\Classic\DTO\Response\Moderator\TournamentClaimDTO;
use App\Classic\Enum\TournamentModerationStatus;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentModerationClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/tournaments', name: 'moderator_tournaments', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] ?ListRequestDTO $dto,
        TournamentModerationClaimRepository $claimRepository,
        Mapper $mapper,
    ): Response {
        $dto ??= new ListRequestDTO();
        $status = TournamentModerationStatus::tryFrom($dto->status) ?? TournamentModerationStatus::Pending;

        return $this->render('moderator/tournaments.html.twig', [
            'claims' => $mapper->mapMultiple($claimRepository->findByStatus($status), TournamentClaimDTO::class),
            'currentStatus' => $status->value,
        ]);
    }
}
