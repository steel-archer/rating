<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RejectRequestDTO
{
    public function __construct(
        #[Assert\Length(max: 1000)]
        #[NoHtml]
        public ?string $comment = null,
    ) {
    }
}
