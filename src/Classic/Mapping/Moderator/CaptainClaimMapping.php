<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Moderator;

use App\Classic\DTO\Response\Moderator\CaptainClaimDTO;
use App\Classic\Entity\CaptainClaim;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: CaptainClaim::class, destination: CaptainClaimDTO::class)]
final class CaptainClaimMapping implements MappingInterface
{
    /**
     * @param CaptainClaim $source
     * @param array{canApprove: bool} $context
     * @return CaptainClaimDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $player = $source->getPlayer();
        $team = $source->getTeam();
        $canApprove = $context['canApprove'] ?? true;

        return new $destinationClass(
            id: $source->getId(),
            playerId: $player->getId(),
            playerName: $player->getFullName(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            comment: $source->getComment(),
            createdAt: $source->getCreatedAt(),
            canApprove: $canApprove,
            cannotApproveReason: $canApprove ? null : 'captain_claim.error.team_full',
        );
    }
}
