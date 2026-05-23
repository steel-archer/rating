<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Dispute;

use App\Common\Attribute\RateLimited;
use App\Classic\DTO\Request\Session\DisputeResolveRequestDTO;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentOfficialRole;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Service\DisputeService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/disputes/resolve/{id}', name: 'my_dispute_resolve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class ResolveController extends AbstractController
{
    public function __invoke(
        TournamentSessionTeamAnswer $answer,
        #[MapRequestPayload] DisputeResolveRequestDTO $dto,
        DisputeService $disputeService,
        TournamentOfficialRepository $officialRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $tournament = $answer->getTournamentSessionTeam()->getTournamentSession()->getTournament();

        if (!$officialRepository->hasRole($user->getPlayer(), $tournament, TournamentOfficialRole::GameJury)) {
            throw $this->createAccessDeniedException();
        }

        $comment = $dto->comment !== null ? trim($dto->comment) : null;
        if ($comment === '') {
            $comment = null;
        }

        try {
            if ($dto->action === 'accept') {
                $disputeService->acceptDispute($answer, $comment);
            } else {
                $disputeService->rejectDispute($answer, $comment);
            }
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (InvalidArgumentException) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
