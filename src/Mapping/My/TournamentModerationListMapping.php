<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TournamentModerationListDTO;
use App\Entity\TournamentModerationClaim;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentModerationClaim::class, destination: TournamentModerationListDTO::class)]
final class TournamentModerationListMapping implements MappingInterface
{
    /**
     * @param TournamentModerationClaim $source
     * @return TournamentModerationListDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            status: $source->getStatus()->value,
            comment: $source->getComment(),
            createdAt: $source->getCreatedAt(),
            resolvedAt: $source->getResolvedAt(),
        );
    }
}
