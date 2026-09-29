<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentDocumentDownload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentDocumentDownload> */
class TournamentDocumentDownloadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentDocumentDownload::class);
    }

    /**
     * @param list<int> $documentIds
     */
    public function deleteByDocumentIds(array $documentIds): void
    {
        if ($documentIds === []) {
            return;
        }

        $this->createQueryBuilder('dd')
            ->delete()
            ->where('IDENTITY(dd.document) IN (:documentIds)')
            ->setParameter('documentIds', $documentIds)
            ->getQuery()
            ->execute();
    }

    /**
     * Returns which of the given players have downloaded any document (question
     * package) of the tournament. A single batch query to avoid N+1.
     *
     * @param list<int> $playerIds
     * @return list<int> Player ids that have at least one download.
     */
    public function findPlayerIdsWhoDownloadedForTournament(Tournament $tournament, array $playerIds): array
    {
        if ($playerIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('dd')
            ->select('DISTINCT IDENTITY(dd.player) AS playerId')
            ->join('dd.document', 'd')
            ->where('d.tournament = :tournament')
            ->andWhere('dd.player IN (:playerIds)')
            ->setParameter('tournament', $tournament)
            ->setParameter('playerIds', $playerIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => (int) $row['playerId'], $rows);
    }
}
