<?php

namespace App\DTO\Response\Tournament;

final readonly class OfficialDTO
{
    public function __construct(
        public string $playerName,
        public string $role,
    ) {
    }
}
