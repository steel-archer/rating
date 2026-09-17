<?php

declare(strict_types=1);

namespace App\Common\Repository;

use App\Common\DTO\Response\SuggestItemDTO;
use App\Common\Entity\Town;
use App\Common\Enum\CacheTag;
use App\Common\Helper\LikeEscape;
use App\Common\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/** @extends ServiceEntityRepository<Town> */
class TownRepository extends ServiceEntityRepository
{
    private const string ONLINE_TOWN_NAME = 'Онлайн';

    public function __construct(
        ManagerRegistry $registry,
        private readonly Mapper $mapper,
        private readonly TagAwareCacheInterface $cache,
    ) {
        parent::__construct($registry, Town::class);
    }

    public function findOnlineTown(): ?Town
    {
        return $this->findOneBy(['name' => self::ONLINE_TOWN_NAME]);
    }

    /**
     * @return list<SuggestItemDTO>
     * @throws InvalidArgumentException
     */
    public function suggest(string $query, ?int $countryId = null): array
    {
        $cacheKey = 'town_suggest_' . md5($query . '_' . ($countryId ?? ''));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $countryId) {
            $item->tag([CacheTag::Towns->value]);
            $item->expiresAfter(86400);

            $qb = $this->createQueryBuilder('t')
                ->select('t.id', 't.name')
                ->where('t.name LIKE :q')
                ->andWhere('t.name != :online')
                ->setParameter('q', LikeEscape::contains($query))
                ->setParameter('online', self::ONLINE_TOWN_NAME)
                ->orderBy('t.name')
                ->setMaxResults(10);

            if ($countryId !== null) {
                $qb->andWhere('t.country = :countryId')
                    ->setParameter('countryId', $countryId);
            }

            $rows = $qb->getQuery()->getArrayResult();

            return $this->mapper->mapMultiple($rows, SuggestItemDTO::class);
        });
    }
}
