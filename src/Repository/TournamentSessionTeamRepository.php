<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSessionTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

class TournamentSessionTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeam::class);
    }

    /**
     * @return list<TournamentSessionTeam>
     */
    public function findByTournamentWithTeam(Tournament $tournament): array
    {
        return $this->createQueryBuilder('st')
            ->join('st.team', 'team')
            ->addSelect('team')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->orderBy('st.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fractional ranking: teams with the same score share the average of their positions.
     *
     * @param list<int> $sessionTeamIds
     * @return array<int, float> sessionTeamId => place
     * @throws Exception
     */
    public function getPlacesInTournament(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();
        $placeholders = $sessionTeamIds
                |> count(...)
                |> (static fn($x) => array_fill(0, $x, '?'))
                |> (static fn($x) => implode(',', $x));

        // 1. Get tournament IDs and scores for requested session teams
        $rows = $conn->fetchAllAssociative(
            "SELECT st.id, st.score, ts.tournament_id
             FROM tournament_session_team st
             JOIN tournament_session ts ON ts.id = st.tournament_session_id
             WHERE st.id IN ($placeholders)",
            $sessionTeamIds,
        );

        $tournamentIds = array_unique(array_column($rows, 'tournament_id'));
        if ($tournamentIds === []) {
            return [];
        }

        // 2. Single query: all scores for all relevant tournaments
        $tournamentPlaceholders = $tournamentIds
                |> count(...)
                |> (static fn($x) => array_fill(0, $x, '?'))
                |> (static fn($x) => implode(',', $x));
        $scoreRows = $conn->fetchAllAssociative(
            "SELECT ts.tournament_id, st.score
             FROM tournament_session_team st
             JOIN tournament_session ts ON ts.id = st.tournament_session_id
             WHERE ts.tournament_id IN ($tournamentPlaceholders)
             ORDER BY ts.tournament_id, st.score DESC",
            array_values($tournamentIds),
        );

        // Group scores by tournament
        $scoresByTournament = [];
        foreach ($scoreRows as $scoreRow) {
            $scoresByTournament[$scoreRow['tournament_id']][] = (int) $scoreRow['score'];
        }

        // 3. Pre-calculate fractional ranks per tournament
        $ranksByTournament = array_map(static function ($scores) {
            return self::fractionalRanks($scores);
        }, $scoresByTournament);

        // 4. Assign places
        $result = [];
        foreach ($rows as $row) {
            $score = (int) $row['score'];
            $result[(int) $row['id']] = $ranksByTournament[$row['tournament_id']][$score] ?? 0;
        }

        return $result;
    }

    /**
     * @param list<int> $sortedScoresDesc
     * @return array<int, float> score => fractional rank
     */
    private static function fractionalRanks(array $sortedScoresDesc): array
    {
        $result = [];
        $position = 1;
        $total = count($sortedScoresDesc);

        while ($position <= $total) {
            $score = $sortedScoresDesc[$position - 1];
            $count = 0;

            while ($position + $count <= $total && $sortedScoresDesc[$position + $count - 1] === $score) {
                $count++;
            }

            $result[$score] = $position + ($count - 1) / 2;
            $position += $count;
        }

        return $result;
    }
}
