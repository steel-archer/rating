<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class OrganizerSessionClaimsDTO
{
    /**
     * @param list<SessionClaimGroupDTO> $pending
     * @param list<SessionClaimGroupDTO> $approved
     * @param list<SessionClaimGroupDTO> $rejected
     */
    public function __construct(
        public array $pending,
        public array $approved,
        public array $rejected,
    ) {
    }
}
