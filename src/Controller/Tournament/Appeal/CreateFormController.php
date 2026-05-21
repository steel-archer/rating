<?php

declare(strict_types=1);

namespace App\Controller\Tournament\Appeal;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\DisputeStatus;
use App\Enum\TournamentStatus;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamAnswerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/appeals/create', name: 'tournament_appeal_create', requirements: ['id' => '\d+'], methods: ['GET'])]
class CreateFormController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        TournamentSessionTeamPlayerRepository $playerRepository,
        TournamentSessionTeamAnswerRepository $answerRepository,
        Mapper $mapper,
    ): Response {
        if ($tournament->getStatus() !== TournamentStatus::Published) {
            throw $this->createNotFoundException();
        }

        if (!$tournament->isAppealOpen()) {
            throw $this->createAccessDeniedException();
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

        // Question numbers with rejected disputes (eligible for "accept" appeal)
        $answers = $answerRepository->findBySessionTeamIds([$sessionTeamId]);
        $rejectedDisputeQuestions = [];
        foreach ($answers as $answer) {
            if ($answer['disputeStatus'] === DisputeStatus::Rejected) {
                $rejectedDisputeQuestions[] = (int) $answer['questionNumber'];
            }
        }

        $totalQuestions = ($tournament->getToursCount() ?? 0) * ($tournament->getQuestionsPerTour() ?? 0);

        return $this->render('tournament/appeal_create.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'totalQuestions' => $totalQuestions,
            'rejectedDisputeQuestions' => $rejectedDisputeQuestions,
        ]);
    }
}
