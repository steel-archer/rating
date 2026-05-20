<?php

declare(strict_types=1);

namespace App\Controller\Tournament\Appeal;

use App\DTO\Response\My\AppealDTO;
use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentStatus;
use App\Mapping\Mapper;
use App\Repository\AppealRepository;
use App\Service\TournamentDisputeAccessService;
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

        return $this->render('tournament/appeals.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'appeals' => $mapper->mapMultiple($appeals, AppealDTO::class),
            'canSubmitAppeal' => $tournament->isAppealOpen(),
        ]);
    }
}
