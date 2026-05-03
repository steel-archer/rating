<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Entity\TournamentSessionTeam;
use App\Helper\FractionalRanking;
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
            ->join('team.town', 'town')
            ->join('st.tournamentSession', 'ts')
            ->addSelect('team', 'town')
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

    private static function placeholders(array $items): string
    {
        return $items
                |> count(...)
                |> (static fn($x) => array_fill(0, $x, '?'))
                |> (static fn($x) => implode(',', $x));
    }
}
