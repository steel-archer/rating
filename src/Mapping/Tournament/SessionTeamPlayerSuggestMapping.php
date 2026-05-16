<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\SessionTeamPlayerSuggestDTO;
use App\Entity\Player;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Player::class, destination: SessionTeamPlayerSuggestDTO::class)]
final class SessionTeamPlayerSuggestMapping implements MappingInterface
{
    /**
     * @param Player $source
     * @param array{group: string, isCaptain?: bool} $context
     * @return SessionTeamPlayerSuggestDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getFullName(),
            group: $context['group'],
            isCaptain: $context['isCaptain'] ?? false,
        );
    }
}
