<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament\Appeal;

use App\Classic\DTO\Response\My\AppealDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentStatus;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Service\TournamentDisputeAccessService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/appeals', name: 'tournament_appeals', requirements: ['id' => '\d+'], methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(
        #[MapEntity(expr: 'repository.findWithSeason(id)')] Tournament $tournament,
        AppealRepository $appealRepository,
        TournamentSessionTeamPlayerRepository $playerRepository,
        TournamentDisputeAccessService $accessService,
        Mapper $mapper,
    ): Response {
        if ($tournament->getStatus() !== TournamentStatus::Published) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if (!$accessService->canView($tournament, $player)) {
            throw $this->createAccessDeniedException();
        }

        $appeals = $appealRepository->findByTournament($tournament);

        $canSubmitAppeal = $tournament->isAppealOpen()
            && $player !== null
            && $playerRepository->findSessionTeamIdByPlayerAndTournament($player, $tournament) !== null;

        return $this->render('tournament/appeals.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'appeals' => $mapper->mapMultiple($appeals, AppealDTO::class),
            'canSubmitAppeal' => $canSubmitAppeal,
        ]);
    }
}
