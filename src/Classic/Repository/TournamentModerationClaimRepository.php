<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentModerationClaim;
use App\Classic\Enum\TournamentModerationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentModerationClaim> */
class TournamentModerationClaimRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentModerationClaim::class);
    }

    public function findByTournament(Tournament $tournament): ?TournamentModerationClaim
    {
        return $this->findOneBy(['tournament' => $tournament]);
    }

    public function findByTournamentId(int $tournamentId): ?TournamentModerationClaim
    {
        return $this->createQueryBuilder('c')
            ->where('c.tournament = :id')
            ->setParameter('id', $tournamentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<Tournament> $tournaments
     * @return array<int, TournamentModerationClaim>
     */
    public function findByTournaments(array $tournaments): array
    {
        if ($tournaments === []) {
            return [];
        }

        $claims = $this->createQueryBuilder('c')
            ->where('c.tournament IN (:tournaments)')
            ->setParameter('tournaments', $tournaments)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($claims as $claim) {
            $indexed[$claim->getTournament()->getId()] = $claim;
        }

        return $indexed;
    }

    /**
     * @return list<TournamentModerationClaim>
     */
    public function findByStatus(TournamentModerationStatus $status): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.tournament', 't')
            ->addSelect('t')
            ->where('c.status = :status')
            ->setParameter('status', $status->value)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
