<?php

declare(strict_types=1);

namespace App\Common\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Manages the site-wide maintenance mode flag.
 *
 * The flag is stored in the default (Redis) cache pool so it can be toggled
 * without a redeploy and survives container restarts. A safety TTL guarantees
 * the site returns to normal operation even if the disable command is missed.
 */
class MaintenanceService
{
    private const string CACHE_KEY = 'maintenance_mode_enabled';

    // Safety net: maintenance mode auto-disables after 24 hours.
    private const int TTL = 86400;

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function enable(): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set(true);
        $item->expiresAfter(self::TTL);

        $this->cache->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function disable(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isEnabled(): bool
    {
        return $this->cache->getItem(self::CACHE_KEY)->isHit();
    }
}
