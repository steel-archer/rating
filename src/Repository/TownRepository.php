<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Response\SuggestItemDTO;
use App\Entity\Town;
use App\Enum\CacheTag;
use App\Helper\LikeEscape;
use App\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/** @extends ServiceEntityRepository<Town> */
class TownRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Mapper $mapper,
        private readonly TagAwareCacheInterface $cache,
    ) {
        parent::__construct($registry, Town::class);
    }

    /**
     * @return list<SuggestItemDTO>
     * @throws InvalidArgumentException
     */
    public function suggest(string $query): array
    {
        $cacheKey = 'town_suggest_' . md5($query);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->tag([CacheTag::Towns->value]);
            $item->expiresAfter(86400);

            $rows = $this->createQueryBuilder('t')
                ->select('t.id', 't.name')
                ->where('t.name LIKE :q')
                ->setParameter('q', LikeEscape::contains($query))
                ->orderBy('t.name')
                ->setMaxResults(10)
                ->getQuery()
                ->getArrayResult();

            return $this->mapper->mapMultiple($rows, SuggestItemDTO::class);
        });
    }
}
