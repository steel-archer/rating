<?php

declare(strict_types=1);

namespace App\Classic\Repository;

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
}
