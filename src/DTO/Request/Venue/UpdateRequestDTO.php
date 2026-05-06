<?php

namespace App\DTO\Request\Venue;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateRequestDTO
{
    /**
     * @param list<int> $representatives
     */
    public function __construct(
        #[Assert\All([new Assert\Positive()])]
        public array $representatives = [],
    ) {
    }
}
