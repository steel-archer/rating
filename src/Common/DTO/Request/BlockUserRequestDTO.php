<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BlockUserRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 500)]
        #[NoHtml]
        public string $reason = '',
    ) {
    }
}
