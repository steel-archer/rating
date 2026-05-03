<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SuggestRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public string $q = '',
    ) {
    }
}
