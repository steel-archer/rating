<?php

declare(strict_types=1);

namespace App\DTO\Response\Tournament;

final readonly class ModerationClaimDTO
{
    public function __construct(
        public string $status,
    ) {
    }
}
