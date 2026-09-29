<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

/**
 * A single host of a session as shown in the extended hosts column: the current
 * host plus any former hosts (recorded in the host history). Former hosts are
 * rendered struck-through/greyed out; hasDownloaded drives the package indicator.
 */
final readonly class SessionHostDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public bool $hasUser,
        public bool $isCurrent,
        public bool $hasDownloaded,
    ) {
    }
}
