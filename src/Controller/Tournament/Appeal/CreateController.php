<?php

declare(strict_types=1);

namespace App\Controller\Tournament\Appeal;

use App\Attribute\RateLimited;
use App\DTO\Request\Session\AppealCreateRequestDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\AppealType;
use App\Enum\TournamentStatus;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Service\AppealService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/appeals/create', name: 'tournament_appeal_store', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class CreateController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        #[MapRequestPayload] AppealCreateRequestDTO $dto,
        TournamentSessionTeamPlayerRepository $playerRepository,
        TournamentSessionTeamAnswerRepository $answerRepository,
        AppealService $appealService,
    ): JsonResponse {
        if ($tournament->getStatus() !== TournamentStatus::Published) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        $sessionTeamId = $playerRepository->findSessionTeamIdByPlayerAndTournament($player, $tournament);
        if ($sessionTeamId === null) {
            throw $this->createAccessDeniedException();
        }

        $answer = $answerRepository->findOneBy([
            'tournamentSessionTeam' => $sessionTeamId,
            'questionNumber' => $dto->questionNumber,
        ]);

        if ($answer === null) {
            return $this->json(['error' => 'common.not_found'], 422);
        }

        try {
            $appealService->create(
                $answer,
                AppealType::from($dto->type),
                trim($dto->text),
            );
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        } catch (InvalidArgumentException) {
            return $this->json(['error' => 'common.error'], 500);
        }

        return $this->json(['success' => true]);
    }
}
