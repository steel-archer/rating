<?php

declare(strict_types=1);

namespace App\DTO\Response;

final readonly class UserContactsDTO
{
    public function __construct(
        public string $email,
        public ?string $telegram,
        public ?string $facebook,
        public ?string $phone,
    ) {
    }
}
