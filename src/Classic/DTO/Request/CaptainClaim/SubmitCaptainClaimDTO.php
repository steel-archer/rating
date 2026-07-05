<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\CaptainClaim;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SubmitCaptainClaimDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $teamId,
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $comment,
    ) {
    }
}
