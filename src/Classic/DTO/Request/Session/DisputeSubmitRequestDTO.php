<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DisputeSubmitRequestDTO
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(max: 100)]
        #[Assert\All([new Assert\Positive()])]
        public array $ids = [],
    ) {
    }
}
