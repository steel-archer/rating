<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Common\Entity\Player;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Classic\Enum\DisputeStatus;
use App\Classic\Enum\TournamentOfficialRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use App\Classic\Entity\TournamentOfficial;

/** @extends ServiceEntityRepository<TournamentSessionTeamAnswer> */
class TournamentSessionTeamAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeamAnswer::class);
    }

    /**
     * @param list<int> $sessionTeamIds
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function hasAnswersForTeams(array $sessionTeamIds): bool
    {
        if ($sessionTeamIds === []) {
            return false;
        }

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @param list<int> $sessionTeamIds
     *
     * @return list<array{teamId: int, questionNumber: int, isCorrect: bool, disputeStatus: DisputeStatus|null, isQuestionRemoved: bool}>
     */
    public function findBySessionTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select(
                'IDENTITY(a.tournamentSessionTeam) AS teamId',
                'a.questionNumber',
                'a.isCorrect',
                'a.disputeStatus',
                'a.isQuestionRemoved',
            )
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param list<int> $sessionTeamIds
     */
    public function submitCreatedDisputes(array $sessionTeamIds): void
    {
        if ($sessionTeamIds === []) {
            return;
        }

        $this->createQueryBuilder('a')
            ->update()
            ->set('a.disputeStatus', ':newStatus')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->andWhere('a.disputeStatus = :oldStatus')
            ->setParameter('ids', $sessionTeamIds)
            ->setParameter('newStatus', DisputeStatus::Submitted->value)
            ->setParameter('oldStatus', DisputeStatus::Created->value)
            ->getQuery()
            ->execute();
    }


    /**
     * @return list<TournamentSessionTeamAnswer>
     */
    public function findDisputesBySession(TournamentSession $session): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.tournamentSessionTeam', 'st')
            ->join('st.team', 'team')
            ->addSelect('st', 'team')
            ->where('st.tournamentSession = :session')
            ->andWhere('a.disputeStatus IS NOT NULL')
            ->setParameter('session', $session)
            ->orderBy('a.questionNumber')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TournamentSessionTeamAnswer>
     */
    public function findSubmittedDisputesByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.tournamentSessionTeam', 'st')
            ->join('st.team', 'team')
            ->join('st.tournamentSession', 's')
            ->addSelect('st', 'team')
            ->where('s.tournament = :tournament')
            ->andWhere('a.disputeStatus IN (:statuses)')
            ->setParameter('tournament', $tournament)
            ->setParameter('statuses', [
                DisputeStatus::Submitted->value,
                DisputeStatus::Accepted->value,
                DisputeStatus::Rejected->value,
            ])
            ->orderBy('a.questionNumber')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return array<int, int> sessionTeamId => count
     */
    public function getAnswerCountsByTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'COUNT(a.id) AS cnt')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->groupBy('a.tournamentSessionTeam')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['teamId']] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return array<int, int> sessionTeamId => score
     */
    public function getScoresByTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.tournamentSessionTeam) AS teamId', 'SUM(CASE WHEN a.isCorrect = true THEN 1 ELSE 0 END) AS score')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->groupBy('a.tournamentSessionTeam')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['teamId']] = (int) $row['score'];
        }

        return $result;
    }

    /**
     * @param list<int> $sessionTeamIds
     */
    public function deleteBySessionTeamIds(array $sessionTeamIds): void
    {
        if ($sessionTeamIds === []) {
            return;
        }

        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return array<int, array<int, int|string>> teamId => [questionNumber => 0|1|disputeText]
     */
    public function getAnswerMapByTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('a')
            ->select(
                'IDENTITY(a.tournamentSessionTeam) AS teamId',
                'a.questionNumber',
                'a.isCorrect',
                'a.disputeText',
            )
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $teamId = (int) $row['teamId'];
            $questionNumber = (int) $row['questionNumber'];

            if ($row['disputeText'] !== null) {
                $map[$teamId][$questionNumber] = $row['disputeText'];
            } else {
                $map[$teamId][$questionNumber] = $row['isCorrect'] ? 1 : 0;
            }
        }

        return $map;
    }

    /**
     * @param list<int> $answerIds
     *
     * @return list<TournamentSessionTeamAnswer>
     */
    public function findDisputesBySessionAndIds(TournamentSession $session, array $answerIds): array
    {
        if ($answerIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->join('a.tournamentSessionTeam', 'st')
            ->where('st.tournamentSession = :session')
            ->andWhere('a.id IN (:ids)')
            ->andWhere('a.disputeStatus IS NOT NULL')
            ->setParameter('session', $session)
            ->setParameter('ids', $answerIds)
            ->getQuery()
            ->getResult();
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
                'SUM(CASE WHEN a.disputeStatus IN (:resolvedStatuses) THEN 1 ELSE 0 END) AS resolved',
            )
            ->from(TournamentSessionTeamAnswer::class, 'a')
            ->join('a.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 's')->join('s.tournament', 't')
            ->join(TournamentOfficial::class, 'o', 'WITH', 'o.tournament = t AND o.player = :player AND o.role = :role')
            ->where('a.disputeStatus IN (:allStatuses)')
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::GameJury)
            ->setParameter('resolvedStatuses', [
                DisputeStatus::Accepted->value,
                DisputeStatus::Rejected->value,
            ])
            ->setParameter('allStatuses', [
                DisputeStatus::Submitted->value,
                DisputeStatus::Accepted->value,
                DisputeStatus::Rejected->value,
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

    public function countSubmittedDisputesByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 's')
            ->where('s.tournament = :tournament')
            ->andWhere('a.disputeStatus IN (:statuses)')
            ->setParameter('tournament', $tournament)
            ->setParameter('statuses', [
                DisputeStatus::Submitted->value,
                DisputeStatus::Accepted->value,
                DisputeStatus::Rejected->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return list<int>
     */
    public function findIdsBySessionTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select('a.id')
            ->where('a.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
