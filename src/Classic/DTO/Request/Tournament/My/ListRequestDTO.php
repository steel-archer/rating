<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Tournament\My;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListRequestDTO
{
    public function __construct(
        #[Assert\Choice(choices: ['asc', 'desc'])]
        public string $sort = 'desc',

        #[Assert\Range(min: 1, max: 10000)]
        public int $page = 1,
    ) {
    }
}
