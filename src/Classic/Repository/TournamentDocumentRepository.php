<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocument;
use App\Classic\Enum\TournamentDocumentVisibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentDocument> */
class TournamentDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentDocument::class);
    }

    /**
     * @param Tournament $tournament
     * @param TournamentDocumentVisibility|null $visibility null returns documents of both kinds
     * @return list<TournamentDocument>
     */
    public function findByTournament(
        Tournament $tournament,
        ?TournamentDocumentVisibility $visibility = null,
    ): array {
        $builder = $this->createQueryBuilder('d')
            ->where('d.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('d.createdAt', 'ASC');

        if ($visibility !== null) {
            $builder
                ->andWhere('d.visibility = :visibility')
                ->setParameter('visibility', $visibility);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Counts closed documents only: the map feeds the question package links of hosts.
     *
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
            ->andWhere('d.visibility = :visibility')
            ->setParameter('tournamentIds', $tournamentIds)
            ->setParameter('visibility', TournamentDocumentVisibility::Closed)
            ->groupBy('d.tournament')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['tournamentId']] = (int) $row['documentCount'];
        }

        return $counts;
    }

    public function countByTournament(
        Tournament $tournament,
        TournamentDocumentVisibility $visibility,
    ): int {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.tournament = :tournament')
            ->andWhere('d.visibility = :visibility')
            ->setParameter('tournament', $tournament)
            ->setParameter('visibility', $visibility)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
