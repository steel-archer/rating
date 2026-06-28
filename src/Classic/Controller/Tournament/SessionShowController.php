<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\Tournament\SessionContextDTO;
use App\Classic\Entity\TournamentSession;
use App\Classic\Enum\TournamentStatus;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Service\SessionResultService;
use App\Classic\Service\TournamentDetailAccessService;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/tournament/{tournamentId}/sessions/{id}',
    name: 'tournament_session_show',
    requirements: ['tournamentId' => '\d+', 'id' => '\d+'],
    methods: ['GET'],
)]
class SessionShowController extends AbstractController
{
    /**
     * @throws DbalException
     * @throws InvalidArgumentException
     */
    public function __invoke(
        int $tournamentId,
        #[MapEntity(expr: 'repository.findWithRelations(id)')] TournamentSession $session,
        TournamentDetailAccessService $detailAccessService,
        TournamentOfficialRepository $officialRepository,
        SessionResultService $resultService,
        Mapper $mapper,
    ): Response {
        $tournament = $session->getTournament();

        if ($tournament->getId() !== $tournamentId) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        // Unpublished tournaments are only visible to owners and moderators
        if ($tournament->getStatus() !== TournamentStatus::Published) {
            $isOwner = $tournament->getCreatedBy()?->getId() === $player?->getId();
            $isModerator = $this->isGranted('ROLE_MODERATOR');

            if (!$isOwner && !$isModerator) {
                throw $this->createNotFoundException();
            }
        }

        // Same visibility logic as tournament results page
        if ($tournament->areResultsHidden()) {
            $isOfficial = $officialRepository->findOneBy([
                'tournament' => $tournament,
                'player' => $player,
            ]) !== null;

            if (!$isOfficial) {
                return $this->render('tournament/session_results_hidden.html.twig', [
                    'session' => $mapper->map($session, SessionContextDTO::class),
                    'hiddenUntil' => $tournament->getResultsHiddenUntil(),
                ]);
            }
        }

        $teams = $resultService->getSessionResults($session);
        $canViewDetails = $detailAccessService->canView($tournament, $player);

        $breakdown = $canViewDetails
            ? $resultService->getAnswerBreakdown($session)
            : [];

        return $this->render('tournament/session_show.html.twig', [
            'session' => $mapper->map($session, SessionContextDTO::class),
            'teams' => $teams,
            'canViewDetails' => $canViewDetails,
            'breakdown' => $breakdown,
        ]);
    }
}
