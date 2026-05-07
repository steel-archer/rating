<?php

declare(strict_types=1);

namespace App\DTO\Request\Tournament\Moderation;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RejectRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 1000)]
        public ?string $comment = null,
    ) {
    }
}
