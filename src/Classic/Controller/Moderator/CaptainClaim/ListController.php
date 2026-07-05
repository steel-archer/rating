<?php

declare(strict_types=1);

namespace App\Classic\Controller\Moderator\CaptainClaim;

use App\Classic\DTO\Response\Moderator\CaptainClaimDTO;
use App\Classic\DTO\Response\Moderator\CaptainClaimResolvedDTO;
use App\Classic\Enum\CaptainClaimStatus;
use App\Classic\Repository\CaptainClaimRepository;
use App\Classic\Service\CaptainClaimService;
use App\Common\Mapping\Mapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/captain-claims', name: 'moderator_captain_claims', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(
        CaptainClaimRepository $claimRepository,
        CaptainClaimService $service,
        Mapper $mapper,
    ): Response {
        $pendingClaims = $claimRepository->findByStatus(CaptainClaimStatus::Pending);

        $pendingDtos = array_map(
            static fn($claim) => $mapper->map($claim, CaptainClaimDTO::class, [
                'canApprove' => $service->canApprove($claim),
            ]),
            $pendingClaims,
        );

        $resolvedDtos = $mapper->mapMultiple(
            $claimRepository->findResolved(),
            CaptainClaimResolvedDTO::class,
        );

        return $this->render('moderator/captain_claims.html.twig', [
            'claims' => $pendingDtos,
            'resolvedClaims' => $resolvedDtos,
        ]);
    }
}
