<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class DisputeDTO
{
    public function __construct(
        public int $id,
        public int $questionNumber,
        public string $text,
        public string $status,
        public ?string $comment,
        public ?string $teamName,
    ) {
    }
}
