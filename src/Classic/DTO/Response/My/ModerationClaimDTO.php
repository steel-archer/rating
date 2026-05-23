<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class ModerationClaimDTO
{
    public function __construct(
        public string $status,
        public ?string $comment,
    ) {
    }
}
