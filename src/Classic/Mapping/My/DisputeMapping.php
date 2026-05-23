<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\DisputeDTO;
use App\Classic\Entity\TournamentSessionTeamAnswer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
