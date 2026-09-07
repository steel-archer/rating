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

    /**
     * @param list<int> $tournamentIds
     * @return array<int, int> map of tournamentId => document count
     */
    public function countByTournamentIds(array $tournamentIds): array
    {
        if ($tournamentIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.tournament) AS tournamentId', 'COUNT(d.id) AS documentCount')
            ->where('IDENTITY(d.tournament) IN (:tournamentIds)')
            ->setParameter('tournamentIds', $tournamentIds)
            ->groupBy('d.tournament')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['tournamentId']] = (int) $row['documentCount'];
        }

        return $counts;
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
