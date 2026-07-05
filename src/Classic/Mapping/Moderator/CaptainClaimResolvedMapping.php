<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Moderator;

use App\Classic\DTO\Response\Moderator\CaptainClaimResolvedDTO;
use App\Classic\Entity\CaptainClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: CaptainClaim::class, destination: CaptainClaimResolvedDTO::class)]
final class CaptainClaimResolvedMapping implements MappingInterface
{
    /**
     * @param CaptainClaim $source
     * @return CaptainClaimResolvedDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();
        $team = $source->getTeam();

        return new $destinationClass(
            id: $source->getId(),
            playerName: $player->getFullName(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            status: $source->getStatus()->value,
            comment: $source->getComment(),
            moderatorComment: $source->getModeratorComment(),
            createdAt: $source->getCreatedAt(),
            resolvedAt: $source->getResolvedAt(),
        );
    }
}
