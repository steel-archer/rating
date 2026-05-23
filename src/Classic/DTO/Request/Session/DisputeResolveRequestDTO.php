<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class DisputeResolveRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['accept', 'reject'])]
        public ?string $action = null,

        #[Assert\Length(max: 500)]
        public ?string $comment = null,
    ) {
    }
}
