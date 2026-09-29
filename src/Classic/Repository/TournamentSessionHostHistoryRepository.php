<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionHostHistory;
use App\Common\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSessionHostHistory> */
class TournamentSessionHostHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionHostHistory::class);
    }

    public function deleteBySession(TournamentSession $session): void
    {
        $this->createQueryBuilder('h')
            ->delete()
            ->where('h.session = :session')
            ->setParameter('session', $session)
            ->getQuery()
            ->execute();
    }

    public function existsForSessionAndPlayer(TournamentSession $session, Player $player): bool
    {
        return (bool) $this->createQueryBuilder('h')
            ->select('1')
            ->where('h.session = :session')
            ->andWhere('h.player = :player')
            ->setParameter('session', $session)
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns, for the given sessions, the list of every player that has ever
     * been their host (a light projection to avoid N+1 and entity hydration).
     *
     * @param list<int> $sessionIds
     * @return array<int, list<array{playerId: int, playerName: string, hasUser: bool}>>
     *         Keyed by session id, ordered by the moment the host was recorded.
     */
    public function findHostsBySessionIds(array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('h')
            ->select(
                'IDENTITY(h.session) AS sessionId',
                'p.id AS playerId',
                'p.lastName AS lastName',
                'p.firstName AS firstName',
                'p.patronymic AS patronymic',
                'u.id AS userId',
            )
            ->join('h.player', 'p')
            ->leftJoin('p.user', 'u')
            ->where('h.session IN (:sessionIds)')
            ->setParameter('sessionIds', $sessionIds)
            ->orderBy('h.createdAt', 'ASC')
            ->addOrderBy('h.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $sessionId = (int) $row['sessionId'];
            $result[$sessionId][] = [
                'playerId' => (int) $row['playerId'],
                'playerName' => $this->composeName($row['lastName'], $row['firstName'], $row['patronymic']),
                'hasUser' => $row['userId'] !== null,
            ];
        }

        return $result;
    }

    /**
     * Returns, for the given players, the venue names of the tournament sessions
     * where each player has ever been recorded as a host. Used to block entering
     * a former host as a squad player in the same tournament.
     *
     * @param list<int> $playerIds
     * @return array<int, list<string>> Keyed by player id, venue names deduplicated.
     */
    public function findHostedVenueNamesByTournament(Tournament $tournament, array $playerIds): array
    {
        if ($playerIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('h')
            ->select('IDENTITY(h.player) AS playerId', 'v.name AS venueName')
            ->join('h.session', 's')
            ->join('s.venue', 'v')
            ->where('s.tournament = :tournament')
            ->andWhere('h.player IN (:playerIds)')
            ->setParameter('tournament', $tournament)
            ->setParameter('playerIds', $playerIds)
            ->orderBy('v.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $playerId = (int) $row['playerId'];
            $venueName = (string) $row['venueName'];
            if (!isset($result[$playerId])) {
                $result[$playerId] = [];
            }
            if (!in_array($venueName, $result[$playerId], true)) {
                $result[$playerId][] = $venueName;
            }
        }

        return $result;
    }

    private function composeName(string $lastName, string $firstName, ?string $patronymic): string
    {
        return [$lastName, $firstName, $patronymic]
            |> array_filter(...)
            |> (static fn(array $parts) => implode(' ', $parts));
    }
}
