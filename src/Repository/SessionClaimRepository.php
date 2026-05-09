<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\SessionClaim;
use App\Entity\Tournament;
use App\Entity\TournamentSession;
use App\Enum\SessionClaimStatus;
use App\Enum\TournamentOfficialRole;
use DateTimeImmutable;
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
     * @return list<array{tournamentId: int, tournamentName: string, claims: list<SessionClaim>}>
     */
    public function findPendingByOrganizer(Player $player): array
    {
        return $this->findGroupedByOrganizer($player, SessionClaimStatus::Pending);
    }

    /**
     * @return list<array{tournamentId: int, tournamentName: string, claims: list<SessionClaim>}>
     */
    public function findActiveByOrganizer(Player $player): array
    {
        return $this->findGroupedByOrganizer($player, SessionClaimStatus::Approved, activeOnly: true);
    }

    public function hasApprovedByPlayerAndTournament(Player $player, Tournament $tournament): bool
    {
        return (bool) $this->createQueryBuilder('sc')
            ->select('1')
            ->join('sc.session', 's')
            ->where('s.representative = :player')
            ->andWhere('s.tournament = :tournament')
            ->andWhere('sc.status = :status')
            ->setParameter('player', $player)
            ->setParameter('tournament', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<SessionClaim>
     */
    public function findByPlayer(Player $player): array
    {
        return $this->createQueryBuilder('sc')
            ->join('sc.session', 's')
            ->join('s.tournament', 't')
            ->join('s.venue', 'v')
            ->join('v.town', 'town')
            ->addSelect('s', 't', 'v', 'town')
            ->where('sc.player = :player')
            ->setParameter('player', $player)
            ->orderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{tournamentId: int, tournamentName: string, claims: list<SessionClaim>}>
     */
    private function findGroupedByOrganizer(
        Player $player,
        SessionClaimStatus $status,
        bool $activeOnly = false,
    ): array {
        $qb = $this->createQueryBuilder('sc')
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
            ->where('sc.status = :status')
            ->andWhere('t.id IN (
                SELECT IDENTITY(o.tournament)
                FROM App\Entity\TournamentOfficial o
                WHERE o.player = :player AND o.role = :role
            )')
            ->setParameter('status', $status->value)
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer->value)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('sc.createdAt', 'DESC');

        if ($activeOnly) {
            $qb->andWhere('(t.endedAt IS NULL OR t.endedAt > :now)')
                ->setParameter('now', new DateTimeImmutable());
        }

        $claims = $qb->getQuery()->getResult();

        $grouped = [];
        foreach ($claims as $claim) {
            $tournament = $claim->getSession()->getTournament();
            $tournamentId = $tournament->getId();
            if (!isset($grouped[$tournamentId])) {
                $grouped[$tournamentId] = [
                    'tournamentId' => $tournamentId,
                    'tournamentName' => $tournament->getName(),
                    'claims' => [],
                ];
            }
            $grouped[$tournamentId]['claims'][] = $claim;
        }

        return array_values($grouped);
    }
}
