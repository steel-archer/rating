<?php

declare(strict_types=1);

namespace App\Mapping;

use App\DTO\Response\UserContactsDTO;
use App\Entity\User;

#[AsMapper(source: User::class, destination: UserContactsDTO::class)]
final class UserContactsMapping implements MappingInterface
{
    /**
     * @param User $source
     * @return UserContactsDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            email: $source->getEmail(),
            telegram: $source->getTelegram(),
            facebook: $source->getFacebook(),
            phone: $source->getPhone(),
        );
    }
}
