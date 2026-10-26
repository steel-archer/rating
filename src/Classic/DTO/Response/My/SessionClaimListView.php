<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

/**
 * The session claims list split into the two blocks shown on the page: claims
 * that still need attention ("current") and terminal ones ("closed"). Assembled
 * by SessionClaimListBuilder so the controller stays thin.
 */
final readonly class SessionClaimListView
{
    /**
     * @param list<SessionClaimListDTO> $currentClaims
     * @param list<SessionClaimListDTO> $closedClaims
     */
    public function __construct(
        public array $currentClaims,
        public array $closedClaims,
    ) {
    }

    public function hasClaims(): bool
    {
        return $this->currentClaims !== [] || $this->closedClaims !== [];
    }
}
