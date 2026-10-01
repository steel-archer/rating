<?php

declare(strict_types=1);

namespace App\Common\Controller\Api;

use App\Common\Attribute\RateLimited;
use App\Common\Entity\User;
use App\Common\Service\MenuCounterService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns the current navigation menu action counts as JSON so the client can
 * refresh the badges after a partial page update (without a full reload).
 */
#[Route('/api/menu-counts', name: 'api_menu_counts', methods: ['GET'])]
#[RateLimited('read')]
class MenuCountsController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(MenuCounterService $menuCounterService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $moderation = null;
        if ($this->isGranted('ROLE_MODERATOR')) {
            $counts = $menuCounterService->getModerationCounts();
            $moderation = [
                'total' => $counts->getTotal(),
                'playerClaims' => $counts->playerClaims,
                'tournaments' => $counts->tournaments,
                'captainClaims' => $counts->captainClaims,
                'venues' => $counts->venues,
            ];
        }

        $my = null;
        $player = $user->getPlayer();
        if ($player !== null) {
            $counts = $menuCounterService->getMyActionCounts($player);
            $my = [
                'total' => $counts->getTotal(),
                'organizerClaims' => $counts->organizerClaims,
                'disputes' => $counts->disputes,
                'appeals' => $counts->appeals,
            ];
        }

        return $this->json([
            'moderation' => $moderation,
            'my' => $my,
        ]);
    }
}
