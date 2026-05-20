<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Appeal;
use App\Entity\Player;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentSessionTeamAnswer;
use App\Enum\AppealStatus;
use App\Enum\TournamentOfficialRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Appeal> */
class AppealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appeal::class);
    }

    public function findByAnswer(TournamentSessionTeamAnswer $answer): ?Appeal
    {
        return $this->findOneBy(['tournamentSessionTeamAnswer' => $answer]);
    }

    /**
     * @return list<Appeal>
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.tournamentSessionTeamAnswer', 'ans')
            ->join('ans.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 's')
            ->join('st.team', 'team')
            ->addSelect('ans', 'st', 'team')
            ->where('s.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('ans.questionNumber')
            ->getQuery()
            ->getResult();
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.tournamentSessionTeamAnswer', 'ans')
            ->join('ans.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 's')
            ->where('s.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{tournamentId: int, tournamentName: string, total: int, resolved: int}>
     */
    public function findJuryTournamentStats(Player $player): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select(
                't.id AS tournamentId',
                't.name AS tournamentName',
                'COUNT(a.id) AS total',
                'SUM(CASE WHEN a.status IN (:resolvedStatuses) THEN 1 ELSE 0 END) AS resolved',
            )
            ->from(Appeal::class, 'a')
            ->join('a.tournamentSessionTeamAnswer', 'ans')
            ->join('ans.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 's')
            ->join('s.tournament', 't')
            ->join(
                TournamentOfficial::class,
                'o',
                'WITH',
                'o.tournament = t AND o.player = :player AND o.role = :role',
            )
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::AppealJury)
            ->setParameter('resolvedStatuses', [
                AppealStatus::Accepted->value,
                AppealStatus::Rejected->value,
            ])
            ->groupBy('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'tournamentId' => (int) $row['tournamentId'],
                'tournamentName' => $row['tournamentName'],
                'total' => (int) $row['total'],
                'resolved' => (int) $row['resolved'],
            ];
        }

        return $result;
    }
}
