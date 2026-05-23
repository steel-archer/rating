<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use App\Classic\Enum\AppealType;
use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AppealCreateRequestDTO
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?int $questionNumber = null,

        #[Assert\NotBlank]
        #[Assert\Choice(callback: [AppealType::class, 'values'])]
        public ?string $type = null,

        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        #[NoHtml]
        public ?string $text = null,
    ) {
    }
}
