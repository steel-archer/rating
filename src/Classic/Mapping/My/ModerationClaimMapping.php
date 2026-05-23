<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\ModerationClaimDTO;
use App\Classic\Entity\TournamentModerationClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
            comment: $source->getComment(),
        );
    }
}
