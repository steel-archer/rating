<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\SessionTeamPlayerSuggestDTO;
use App\Common\Entity\Player;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
