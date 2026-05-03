<?php

namespace App\Mapping;

use App\DTO\Response\Tournament\OfficialDTO;
use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use InvalidArgumentException;

#[AsMapper(source: Tournament::class, destination: TournamentDTO::class)]
final class TournamentMapping implements MappingInterface
{
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var Tournament $source */
        $mapper = $context['mapper'] ?? throw new InvalidArgumentException('Mapper is required in context');

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            seasonName: $source->getSeason()?->getName(),
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
            toursCount: $source->getToursCount(),
            questionsPerTour: $source->getQuestionsPerTour(),
            difficulty: $source->getDifficulty(),
            trueDl: $source->getTrueDl(),
            teamCount: $context['teamCount'] ?? 0,
            officials: self::groupOfficials($mapper, $context['officials'] ?? []),
        );
    }

    /**
     * @return array<string, list<OfficialDTO>>
     */
    private static function groupOfficials(Mapper $mapper, array $officials): array
    {
        $grouped = [];
        foreach ($officials as $official) {
            $dto = $mapper->map($official, OfficialDTO::class);
            $grouped[$dto->role][] = $dto;
        }

        return $grouped;
    }
}
