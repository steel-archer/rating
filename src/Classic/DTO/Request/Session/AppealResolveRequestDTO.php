<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Session;

use App\Classic\Enum\ResolveAction;
use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AppealResolveRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [ResolveAction::class, 'values'])]
        public ?string $action = null,

        #[Assert\Length(max: 5000)]
        #[NoHtml]
        public ?string $verdict = null,
    ) {
    }
}
