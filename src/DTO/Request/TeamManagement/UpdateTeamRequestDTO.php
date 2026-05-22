<?php

declare(strict_types=1);

namespace App\DTO\Request\TeamManagement;

use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTeamRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[NoHtml]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $townId,
    ) {
    }
}
