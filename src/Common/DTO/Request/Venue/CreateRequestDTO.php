<?php

declare(strict_types=1);

namespace App\Common\DTO\Request\Venue;

use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    'this.isOnline or this.townId !== null',
    message: 'venue.error.town_required',
)]
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

        #[Assert\Length(max: 2000)]
        public ?string $description = null,

        #[Assert\Length(max: 255)]
        #[Assert\Url(protocols: ['http', 'https'])]
        public ?string $url = null,
    ) {
    }
}
