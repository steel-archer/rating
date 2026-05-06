<?php

namespace App\Controller\Moderator\PlayerClaim;

use App\Exception\PlayerClaimException;
use App\Service\PlayerClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderator/player-claims/{id}/approve', name: 'moderator_player_claim_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[IsGranted('ROLE_MODERATOR')]
#[IsCsrfTokenValid(new Expression("'player_claim_' ~ args['id']"))]
final class ApproveController extends AbstractController
{
    public function __invoke(int $id, PlayerClaimService $claimService): Response
    {
        try {
            $claimService->approve($id);
        } catch (PlayerClaimException $ex) {
            $this->addFlash('error', $ex->getMessage());
        }

        return $this->redirectToRoute('moderator_player_claims');
    }
}
