<?php

declare(strict_types=1);

namespace App\Common\DTO\Response;

final readonly class SuggestItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $townName = null,
        public ?string $countryName = null,
    ) {
    }
}
