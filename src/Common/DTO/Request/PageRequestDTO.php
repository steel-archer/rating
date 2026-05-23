<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PageRequestDTO
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(10000)]
        public int $page = 1,
    ) {
    }
}
