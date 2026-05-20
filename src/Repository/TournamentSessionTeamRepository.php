<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Helper\FractionalRanking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSessionTeam> */
class TournamentSessionTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeam::class);
    }

    /**
     * @return list<TournamentSessionTeam>
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.tournamentSession', 'ts')
            ->leftJoin('st.answers', 'a')
            ->addSelect('a')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TournamentSessionTeam>
     */
    public function findBySessionWithTeamAndTown(TournamentSession $session): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.team', 'team')
            ->join('team.town', 'town')
            ->addSelect('team', 'town')
            ->where('st.tournamentSession = :session')
            ->setParameter('session', $session)
            ->orderBy('team.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('st')
            ->select('COUNT(st.id)')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countByTeam(Team $team): int
    {
        return (int) $this->createQueryBuilder('st')
            ->select('COUNT(st.id)')
            ->where('st.team = :team')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param list<int> $sessionIds
     * @return array<int, int> sessionId => count
     */
    public function countBySessionIds(array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('st')
            ->select('IDENTITY(st.tournamentSession) AS sessionId', 'COUNT(st.id) AS cnt')
            ->where('st.tournamentSession IN (:ids)')
            ->setParameter('ids', $sessionIds)
            ->groupBy('st.tournamentSession')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['sessionId']] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * @return list<TournamentSessionTeam>
     */
    public function findByTeamPaginated(Team $team, int $page, int $perPage): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.tournamentSession', 'ts')
            ->join('ts.tournament', 'tournament')
            ->leftJoin('tournament.season', 'season')
            ->addSelect('ts', 'tournament', 'season')
            ->where('st.team = :team')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('team', $team)
            ->orderBy('ts.playedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TournamentSessionTeam>
     */
    public function findByTournamentPaginated(Tournament $tournament, int $page, int $perPage): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.team', 'team')
            ->join('team.town', 'town')
            ->join('st.tournamentSession', 'ts')
            ->addSelect('team', 'town')
            ->where('ts.tournament = :t')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('t', $tournament)
            ->orderBy('st.score', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<int>
     */
    public function findTeamIdsByTournament(Tournament $tournament): array
    {
        $rows = $this->createQueryBuilder('st')
            ->select('DISTINCT IDENTITY(st.team) AS teamId')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => (int) $row['teamId'], $rows);
    }

    /**
     * Fractional ranking: teams with the same score share the average of their positions.
     *
     * @param list<int> $sessionTeamIds
     * @throws DbalException
     * @return array<int, float> sessionTeamId => place
     */
    public function getPlacesInTournament(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $rows = $conn->fetchAllAssociative(
            'SELECT st.id, st.score, ts.tournament_id
             FROM tournament_session_team st
             JOIN tournament_session ts ON ts.id = st.tournament_session_id
             WHERE st.id IN (' . self::placeholders($sessionTeamIds) . ')',
            $sessionTeamIds,
        );

        $tournamentIds = array_unique(array_column($rows, 'tournament_id'));
        if ($tournamentIds === []) {
            return [];
        }

        $tournamentIdsValues = array_values($tournamentIds);
        $scoreRows = $conn->fetchAllAssociative(
            'SELECT ts.tournament_id, st.score
             FROM tournament_session_team st
             JOIN tournament_session ts ON ts.id = st.tournament_session_id
             WHERE ts.tournament_id IN (' . self::placeholders($tournamentIdsValues) . ')
               AND st.results_submitted = 1
             ORDER BY ts.tournament_id, st.score DESC',
            $tournamentIdsValues,
        );

        $scoresByTournament = [];
        foreach ($scoreRows as $scoreRow) {
            $scoresByTournament[$scoreRow['tournament_id']][] = (int) $scoreRow['score'];
        }

        $ranksByTournament = array_map(FractionalRanking::rank(...), $scoresByTournament);

        $result = [];
        foreach ($rows as $row) {
            $score = (int) $row['score'];
            $result[(int) $row['id']] = $ranksByTournament[$row['tournament_id']][$score] ?? 0;
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    public function findUnsubmittedIdsBySession(TournamentSession $session): array
    {
        $rows = $this->createQueryBuilder('st')
            ->select('st.id')
            ->where('st.tournamentSession = :session')
            ->andWhere('st.resultsSubmitted = false')
            ->setParameter('session', $session)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => (int) $row['id'], $rows);
    }

    /** @param list<mixed> $items */
    private static function placeholders(array $items): string
    {
        return $items
                |> count(...)
                |> (static fn($x) => array_fill(0, $x, '?'))
                |> (static fn($x) => implode(',', $x));
    }
}
