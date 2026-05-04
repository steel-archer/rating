<?php

namespace App\Controller\Moderator;

use App\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/claims/{id}/approve', name: 'moderator_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
#[IsCsrfTokenValid(new Expression("'claim_' ~ args['id']"))]
final class ClaimApproveController extends AbstractController
{
    public function __invoke(int $id, PlayerClaimService $claimService): Response
    {
        $claimService->approve($id);

        return $this->redirectToRoute('moderator_claims');
    }
}
