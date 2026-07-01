<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\Tournament\My;

use App\Classic\Enum\TournamentFormat;
use App\Classic\Enum\TournamentOnlineMode;
use App\Common\Validator\NoHtml;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[NoHtml]
        public string $name = '',

        #[Assert\NotNull]
        public TournamentFormat $format = TournamentFormat::Distributed,

        #[Assert\NotNull]
        public TournamentOnlineMode $onlineMode = TournamentOnlineMode::Mixed,
    ) {
    }
}
