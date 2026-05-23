<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\Tournament;

final readonly class ModerationClaimDTO
{
    public function __construct(
        public string $status,
    ) {
    }
}
