<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class ResultsSessionDTO
{
    public function __construct(
        public int $id,
        public int $toursCount,
        public int $questionsPerTour,
    ) {
    }
}
