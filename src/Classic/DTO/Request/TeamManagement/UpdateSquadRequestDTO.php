<?php

declare(strict_types=1);

namespace App\Classic\DTO\Request\TeamManagement;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateSquadRequestDTO
{
    /**
     * @param list<int> $addPlayerIds
     * @param list<int> $removePlayerIds
     */
    public function __construct(
        #[Assert\All([new Assert\Positive()])]
        public array $addPlayerIds = [],
        #[Assert\All([new Assert\Positive()])]
        public array $removePlayerIds = [],
        #[Assert\Positive]
        public ?int $newCaptainId = null,
    ) {
    }
}
