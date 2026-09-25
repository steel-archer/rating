<?php

declare(strict_types=1);

namespace App\Common\DTO\Request\Venue;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateRequestDTO
{
    /**
     * @param list<int> $representatives
     */
    public function __construct(
        #[Assert\Length(max: 2000)]
        public ?string $description = null,

        #[Assert\Length(max: 255)]
        #[Assert\Url(protocols: ['http', 'https'])]
        public ?string $url = null,

        #[Assert\Count(max: 10)]
        #[Assert\All([new Assert\Positive()])]
        public array $representatives = [],
    ) {
    }
}
