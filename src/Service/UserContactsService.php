<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Request\HasContactFields;
use App\DTO\Response\UserContactsDTO;
use App\Entity\User;
use App\Mapping\Mapper;

class UserContactsService
{
    public function __construct(
        private Mapper $mapper,
    ) {
    }

    public function updateFromDto(User $user, HasContactFields $dto): void
    {
        $user->setTelegram($dto->getTelegram() ?: null);
        $user->setFacebook($dto->getFacebook() ?: null);
        $user->setPhone($dto->getPhone() ?: null);
    }

    public function getContacts(?User $user): UserContactsDTO
    {
        if ($user === null) {
            return UserContactsDTO::empty();
        }

        /** @var UserContactsDTO */
        return $this->mapper->map($user, UserContactsDTO::class);
    }
}
