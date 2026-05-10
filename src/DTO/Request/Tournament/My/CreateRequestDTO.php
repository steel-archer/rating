<?php

declare(strict_types=1);

namespace App\DTO\Request\Tournament\My;

use App\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[NoHtml]
        public string $name = '',
    ) {
    }
}
