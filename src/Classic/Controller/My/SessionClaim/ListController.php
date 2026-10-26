<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Classic\Service\SessionClaimListBuilder;
use App\Common\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims', name: 'my_session_claims', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(SessionClaimListBuilder $listBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($player === null) {
            throw $this->createNotFoundException();
        }

        $view = $listBuilder->build($player);

        return $this->render('my/session_claims.html.twig', [
            'currentClaims' => $view->currentClaims,
            'closedClaims' => $view->closedClaims,
            'hasClaims' => $view->hasClaims(),
        ]);
    }
}
