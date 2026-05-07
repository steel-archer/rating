<?php

declare(strict_types=1);

namespace App\Controller\Moderator\Tournament;

use App\DTO\Request\Tournament\Moderation\ListRequestDTO;
use App\DTO\Response\Moderator\TournamentClaimDTO;
use App\Enum\TournamentModerationStatus;
use App\Mapping\Mapper;
use App\Repository\TournamentModerationClaimRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/tournaments', name: 'moderator_tournaments', methods: ['GET'])]
#[IsGranted('ROLE_MODERATOR')]
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
