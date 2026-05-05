<?php

namespace App\DTO\Request\Tournament\Moderation;

final readonly class ListRequestDTO
{
    public function __construct(
        public string $status = 'pending',
    ) {
    }
}
