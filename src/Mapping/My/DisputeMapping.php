<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\DisputeDTO;
use App\Entity\TournamentSessionTeamAnswer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamAnswer::class, destination: DisputeDTO::class)]
final class DisputeMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeamAnswer $source
     * @return DisputeDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $sessionTeam = $source->getTournamentSessionTeam();

        return new $destinationClass(
            id: $source->getId(),
            questionNumber: $source->getQuestionNumber(),
            text: $source->getDisputeText(),
            status: $source->getDisputeStatus()->value,
            comment: $source->getDisputeComment(),
            teamName: $sessionTeam->getOneTimeName() ?? $sessionTeam->getTeam()->getName(),
        );
    }
}
