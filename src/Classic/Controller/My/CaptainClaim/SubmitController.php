<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\CaptainClaim;

use App\Classic\DTO\Request\CaptainClaim\SubmitCaptainClaimDTO;
use App\Classic\Repository\TeamRepository;
use App\Classic\Service\CaptainClaimService;
use App\Common\Attribute\RateLimited;
use App\Common\Entity\User;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/captain-claim', name: 'my_captain_claim_submit', methods: ['POST'])]
#[RateLimited('mutation')]
class SubmitController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] SubmitCaptainClaimDTO $dto,
        CaptainClaimService $service,
        TeamRepository $teamRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($player === null) {
            return $this->json(['error' => 'common.no_player'], 422);
        }

        $team = $teamRepository->find($dto->teamId);
        if ($team === null) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->submit($player, $team, $dto->comment);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        return $this->json(['success' => true]);
    }
}
