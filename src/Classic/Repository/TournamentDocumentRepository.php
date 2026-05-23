<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentDocument> */
class TournamentDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentDocument::class);
    }

    /** @return list<TournamentDocument> */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
