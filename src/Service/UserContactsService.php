<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\HasContactFields;
use App\Entity\User;

class UserContactsService
{
    public function updateFromDto(User $user, HasContactFields $dto): void
    {
        $user->setTelegram($dto->getTelegram() ?: null);
        $user->setFacebook($dto->getFacebook() ?: null);
        $user->setPhone($dto->getPhone() ?: null);
    }
}
