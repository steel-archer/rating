<?php

namespace App\DTO\Request\Tournament\My;

final readonly class ListRequestDTO
{
    public function __construct(
        public string $sort = 'desc',
    ) {
    }
}
