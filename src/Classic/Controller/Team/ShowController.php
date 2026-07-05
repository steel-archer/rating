<?php

declare(strict_types=1);

namespace App\Classic\Controller\Team;

use App\Classic\Repository\CaptainClaimRepository;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Service\TeamService;
use App\Common\Entity\User;
use App\Common\Repository\SeasonRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team/{id}', name: 'team_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(
        int $id,
        TeamService $teamService,
        SeasonRepository $seasonRepository,
        TeamPlayerRepository $teamPlayerRepository,
        CaptainClaimRepository $claimRepository,
    ): Response {
        $team = $teamService->get($id);

        $canSubmitCaptainClaim = false;

        /** @var User|null $user */
        $user = $this->getUser();
        $player = $user?->getPlayer();

        if ($player !== null) {
            $season = $seasonRepository->findCurrent();
            if ($season !== null) {
                $hasPendingClaim = $claimRepository->findPendingByPlayer($player) !== null;
                $playerEntry = $teamPlayerRepository->findOneBy([
                    'player' => $player,
                    'season' => $season,
                ]);
                $isInAnotherTeam = $playerEntry !== null && $playerEntry->getTeam()->getId() !== $id;
                $isAlreadyCaptain = $playerEntry !== null
                    && $playerEntry->getTeam()->getId() === $id
                    && $playerEntry->isCaptain();

                $canSubmitCaptainClaim = !$hasPendingClaim && !$isInAnotherTeam && !$isAlreadyCaptain;
            }
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
            'canSubmitCaptainClaim' => $canSubmitCaptainClaim,
        ]);
    }
}
