<?php

declare(strict_types=1);

namespace App\Mapping\Tournament;

use App\DTO\Response\Tournament\ModerationClaimDTO;
use App\Entity\TournamentModerationClaim;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentModerationClaim::class, destination: ModerationClaimDTO::class)]
final class ModerationClaimMapping implements MappingInterface
{
    /**
     * @param TournamentModerationClaim $source
     * @return ModerationClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            status: $source->getStatus()->value,
        );
    }
}
