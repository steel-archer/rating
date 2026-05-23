<?php

declare(strict_types=1);

namespace App\Common\DTO\Request;

final readonly class UserContactsRequestDTO implements HasContactFields
{
    use ContactFieldsTrait;

    public function __construct(
        ?string $telegram = null,
        ?string $facebook = null,
        ?string $phone = null,
    ) {
        $this->telegram = $telegram;
        $this->facebook = $facebook;
        $this->phone = $phone;
    }
}
