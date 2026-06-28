<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClaimExistingRequestDTO implements HasContactFields
{
    use ContactFieldsTrait;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $playerId = 0,

        #[Assert\IsTrue(message: 'player_claim.terms_required')]
        public bool $termsAccepted = false,

        ?string $telegram = null,
        ?string $facebook = null,
        ?string $phone = null,
    ) {
        $this->telegram = $telegram;
        $this->facebook = $facebook;
        $this->phone = $phone;
    }
}
