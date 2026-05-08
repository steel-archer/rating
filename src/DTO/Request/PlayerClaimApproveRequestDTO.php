<?php

declare(strict_types=1);

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlayerClaimApproveRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $townName = null,
    ) {
    }
}
