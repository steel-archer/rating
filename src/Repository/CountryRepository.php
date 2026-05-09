<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Response\SuggestItemDTO;
use App\Entity\Country;
use App\Enum\CacheTag;
use App\Helper\LikeEscape;
use App\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/** @extends ServiceEntityRepository<Country> */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Mapper $mapper,
        private readonly TagAwareCacheInterface $cache,
    ) {
        parent::__construct($registry, Country::class);
    }

    /**
     * @return list<SuggestItemDTO>
     */
    public function suggest(string $query): array
    {
        $cacheKey = 'country_suggest_' . md5($query);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->tag([CacheTag::Countries->value]);
            $item->expiresAfter(86400);

            $rows = $this->createQueryBuilder('c')
                ->select('c.id', 'c.name')
                ->where('c.name LIKE :q')
                ->setParameter('q', LikeEscape::contains($query))
                ->orderBy('c.name')
                ->setMaxResults(10)
                ->getQuery()
                ->getArrayResult();

            return $this->mapper->mapMultiple($rows, SuggestItemDTO::class);
        });
    }
}
