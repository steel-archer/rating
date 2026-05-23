<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Moderator;

use App\Classic\DTO\Response\Moderator\TournamentClaimDTO;
use App\Classic\Entity\TournamentModerationClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
