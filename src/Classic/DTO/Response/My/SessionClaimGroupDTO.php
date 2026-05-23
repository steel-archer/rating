<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

use App\Classic\DTO\Response\Tournament\SessionClaimDTO;

final readonly class SessionClaimGroupDTO
{
    /**
     * @param list<SessionClaimDTO> $claims
     */
    public function __construct(
        public int $tournamentId,
        public string $tournamentName,
        public array $claims,
    ) {
    }
}
