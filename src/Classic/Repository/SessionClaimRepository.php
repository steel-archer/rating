<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Common\Entity\Player;
use App\Classic\Entity\SessionClaim;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentOfficial;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Enum\TournamentOfficialRole;
use App\Common\Entity\VenueRepresentative;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SessionClaim> */
class SessionClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionClaim::class);
    }

    public function findBySession(TournamentSession $session): ?SessionClaim
    {
        return $this->findOneBy(['session' => $session]);
    }

    /**
     * Count pending session claims across every tournament the player organizes.
     * Powers the personal menu badge for the organizer.
     */
    public function countPendingForOrganizer(Player $player): int
    {
        return (int) $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->join('sc.session', 's')
            ->join(TournamentOfficial::class, 'o', 'WITH', 'o.tournament = s.tournament AND o.player = :player AND o.role = :role')
            ->where('sc.status = :status')
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer)
            ->setParameter('status', SessionClaimStatus::Pending->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasApprovedHostedSession(Player $host, Tournament $tournament): bool
    {
        return (bool) $this->createQueryBuilder('sc')
            ->select('1')
            ->join('sc.session', 's')
            ->where('s.host = :host')
            ->andWhere('s.tournament = :tournament')
            ->andWhere('sc.status = :status')
            ->setParameter('host', $host)
            ->setParameter('tournament', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<SessionClaim>
     */
    public function findApprovedHostedByPlayer(Player $host): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.tournament', 't')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->addSelect('s', 't', 'v', 'town')
            ->where('s.host = :host')
            ->andWhere('sc.status = :status')
            ->setParameter('host', $host)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->orderBy('s.playedAt', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Claims are shared across every representative of a venue, so a player sees
     * claims for all sessions held at the venues they represent, not only the
     * ones they personally submitted.
     *
     * @return list<SessionClaim>
     */
    public function findByVenueRepresentative(Player $player): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.tournament', 't')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->addSelect('s', 't', 'v', 'town')
            ->where('EXISTS (
                SELECT 1
                FROM ' . VenueRepresentative::class . ' vr
                WHERE vr.venue = v AND vr.player = :player
            )')
            ->setParameter('player', $player)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count teams that have submitted results, grouped by session, in a single batch query.
     *
     * @param list<int> $sessionIds
     * @return array<int, int> sessionId => submitted team count
     */
    public function countSubmittedTeamsBySessionIds(array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(st.tournamentSession) AS sessionId', 'COUNT(st.id) AS teamsCount')
            ->from(TournamentSessionTeam::class, 'st')
            ->where('st.tournamentSession IN (:sessionIds)')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('sessionIds', $sessionIds)
            ->groupBy('st.tournamentSession')
            ->getQuery()
            ->getArrayResult();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['sessionId']] = (int) $row['teamsCount'];
        }

        return $counts;
    }

    /**
     * Load every claim for tournaments the player organizes, in a single fetch-joined query.
     * The caller groups the results by status in memory.
     *
     * @return list<SessionClaim>
     */
    public function findByOrganizer(Player $player): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.tournament', 't')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->join('s.representative', 'rep')
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('s.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->join('sc.player', 'p')
            ->leftJoin('p.user', 'pUser')
            ->addSelect('s', 't', 'v', 'town', 'rep', 'repUser', 'host', 'hostUser', 'p', 'pUser')
            ->where('t.id IN (
                SELECT IDENTITY(o.tournament)
                FROM App\Classic\Entity\TournamentOfficial o
                WHERE o.player = :player AND o.role = :role
            )')
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer->value)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
