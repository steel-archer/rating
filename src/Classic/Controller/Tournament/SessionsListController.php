<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Common\DTO\Request\PageRequestDTO;
use App\Classic\DTO\Response\Tournament\SessionDTO;
use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\Player;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentDocumentDownloadRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentSessionHostHistoryRepository;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use App\Common\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/sessions/list', name: 'tournament_sessions_list', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsListController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentSessionRepository $sessionRepository,
        TournamentSessionTeamRepository $sessionTeamRepository,
        TournamentOfficialRepository $officialRepository,
        TournamentSessionHostHistoryRepository $hostHistoryRepository,
        TournamentDocumentDownloadRepository $documentDownloadRepository,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
        #[MapQueryString] PageRequestDTO $dto = new PageRequestDTO(),
    ): Response {
        $sessions = $sessionRepository->findByTournamentPaginated($tournament, $dto->page);

        $teamCounts = $sessionTeamRepository->countBySessionIds(
            array_map(static fn($s) => $s->getId(), $sessions),
        );

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        $context = $this->buildHostViewContext(
            $tournament,
            $sessions,
            $player,
            $officialRepository,
            $hostHistoryRepository,
            $documentDownloadRepository,
            $representativeRepository,
        );

        return $this->render('tournament/_sessions.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'sessions' => $mapper->mapMultiple($sessions, SessionDTO::class, $context),
            'teamCounts' => $teamCounts,
            'page' => $dto->page,
            'lastPage' => $sessionRepository->getLastPageNumberByTournament($tournament),
        ]);
    }

    /**
     * Builds the mapper context that drives the extended hosts column. The full
     * host history is exposed only for sessions the viewer may inspect:
     * organizers of the tournament and moderators/admins see every session,
     * a venue representative sees every session held at a venue they represent.
     * For other viewers an empty context is returned, so only the current host
     * is shown.
     *
     * @param list<TournamentSession> $sessions
     * @return array<string, mixed>
     */
    private function buildHostViewContext(
        Tournament $tournament,
        array $sessions,
        ?Player $player,
        TournamentOfficialRepository $officialRepository,
        TournamentSessionHostHistoryRepository $hostHistoryRepository,
        TournamentDocumentDownloadRepository $documentDownloadRepository,
        VenueRepresentativeRepository $representativeRepository,
    ): array {
        /*
         * Moderators/admins and organizers of this tournament see every session;
         * a representative sees every session held at a venue they represent.
         */
        $seesAll = $this->isGranted('ROLE_MODERATOR')
            || ($player !== null && $officialRepository->isOrganizer($player, $tournament));

        if (!$seesAll && $player === null) {
            return [];
        }

        $visibleSessionIds = [];
        if ($seesAll) {
            foreach ($sessions as $session) {
                $visibleSessionIds[] = $session->getId();
            }
        } else {
            $venueIds = array_map(
                    static fn(TournamentSession $session): int => $session->getVenue()->getId(),
                    $sessions,
                )
                    |> array_unique(...)
                    |> array_values(...);
            $representedVenueIds = array_flip(
                $representativeRepository->findRepresentedVenueIds($player, $venueIds),
            );

            foreach ($sessions as $session) {
                if (isset($representedVenueIds[$session->getVenue()->getId()])) {
                    $visibleSessionIds[] = $session->getId();
                }
            }
        }

        if ($visibleSessionIds === []) {
            return [];
        }

        $hostsBySession = $hostHistoryRepository->findHostsBySessionIds($visibleSessionIds);

        $hostPlayerIds = [];
        foreach ($hostsBySession as $rows) {
            foreach ($rows as $row) {
                $hostPlayerIds[$row['playerId']] = true;
            }
        }

        $downloadedPlayerIds = $documentDownloadRepository->findPlayerIdsWhoDownloadedForTournament(
            $tournament,
            array_keys($hostPlayerIds),
        );

        return [
            'hostsBySession' => $hostsBySession,
            'downloadedPlayerIds' => $downloadedPlayerIds,
        ];
    }
}
