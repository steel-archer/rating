<?php

declare(strict_types=1);

namespace App\Common\DTO\Request\Venue;

use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[NoHtml]
        public string $name = '',

        public bool $isOnline = false,

        #[Assert\Positive]
        public ?int $townId = null,
    ) {
    }
}
