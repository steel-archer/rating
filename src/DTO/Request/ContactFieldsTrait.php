<?php

declare(strict_types=1);

namespace App\DTO\Request;

use App\Validator\Phone;
use Symfony\Component\Validator\Constraints as Assert;

trait ContactFieldsTrait
{
    #[Assert\Length(max: 32)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]{5,32}$/', message: 'contact.telegram.invalid')]
    public readonly ?string $telegram;

    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9.]{1,50}$/', message: 'contact.facebook.invalid')]
    public readonly ?string $facebook;

    #[Assert\Length(max: 20)]
    #[Phone]
    public readonly ?string $phone;

    public function getTelegram(): ?string
    {
        return $this->telegram;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}
