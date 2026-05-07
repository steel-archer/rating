<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TournamentEditDTO;
use App\Entity\Tournament;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: Tournament::class, destination: TournamentEditDTO::class)]
final class TournamentEditMapping implements MappingInterface
{
    /**
     * @param Tournament $source
     * @return TournamentEditDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            status: $source->getStatus()->value,
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
            toursCount: $source->getToursCount(),
            questionsPerTour: $source->getQuestionsPerTour(),
            difficulty: $source->getDifficulty(),
            createdByPlayerId: $source->getCreatedBy()->getPlayer()->getId(),
        );
    }
}
