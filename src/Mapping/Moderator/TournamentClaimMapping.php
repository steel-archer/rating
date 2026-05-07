<?php

declare(strict_types=1);

namespace App\Mapping\Moderator;

use App\DTO\Response\Moderator\TournamentClaimDTO;
use App\Entity\TournamentModerationClaim;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentModerationClaim::class, destination: TournamentClaimDTO::class)]
final class TournamentClaimMapping implements MappingInterface
{
    /**
     * @param TournamentModerationClaim $source
     * @return TournamentClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $tournament = $source->getTournament();

        return new $destinationClass(
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            createdAt: $source->getCreatedAt(),
            comment: $source->getComment(),
        );
    }
}
