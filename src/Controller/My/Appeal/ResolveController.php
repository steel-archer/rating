<?php

declare(strict_types=1);

namespace App\Controller\My\Appeal;

use App\Attribute\RateLimited;
use App\DTO\Request\Session\AppealResolveRequestDTO;
use App\Entity\Appeal;
use App\Entity\User;
use App\Enum\ResolveAction;
use App\Enum\TournamentOfficialRole;
use App\Repository\TournamentOfficialRepository;
use App\Service\AppealService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/appeals/resolve/{id}', name: 'my_appeal_resolve', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class ResolveController extends AbstractController
{
    public function __invoke(
        Appeal $appeal,
        #[MapRequestPayload] AppealResolveRequestDTO $dto,
        AppealService $appealService,
        TournamentOfficialRepository $officialRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $tournament = $appeal->getTournamentSessionTeamAnswer()
            ->getTournamentSessionTeam()
            ->getTournamentSession()
            ->getTournament();

        if (!$officialRepository->hasRole($user->getPlayer(), $tournament, TournamentOfficialRole::AppealJury)) {
            throw $this->createAccessDeniedException();
        }

        $verdict = $dto->verdict !== null ? trim($dto->verdict) : null;
        if ($verdict === '') {
            $verdict = null;
        }

        try {
            if ($dto->action === ResolveAction::Accept->value) {
                $appealService->accept($appeal, $verdict);
            } else {
                $appealService->reject($appeal, $verdict);
            }
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (InvalidArgumentException) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
