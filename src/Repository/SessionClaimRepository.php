<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\SessionClaim;
use App\Enum\SessionClaimStatus;
use App\Enum\TournamentOfficialRole;
use App\Entity\TournamentSession;
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
        $claims = $this->createQueryBuilder('sc')
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
            ->setParameter('status', SessionClaimStatus::Pending->value)
            ->setParameter('player', $player)
            ->setParameter('role', TournamentOfficialRole::Organizer->value)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('sc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

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
}
