<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class AppealDTO
{
    public function __construct(
        public int $id,
        public string $type,
        public int $questionNumber,
        public string $status,
        public string $text,
        public ?string $verdict,
    ) {
    }
}
