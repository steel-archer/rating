<?php

declare(strict_types=1);

namespace App\Common\Service;

/**
 * Exposes whether the site is running in alpha-testing mode.
 *
 * The flag is a plain boolean environment variable so it can be toggled per
 * environment (via deploy configuration) without editing tracked .env files.
 * It stays enabled for the whole testing period, so no cache or TTL is used.
 */
final readonly class AlphaTestingService
{
    public function __construct(
        private bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
