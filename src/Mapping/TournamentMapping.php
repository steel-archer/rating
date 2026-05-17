<?php

declare(strict_types=1);

namespace App\Mapping;

use App\DTO\Response\Tournament\OfficialDTO;
use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use InvalidArgumentException;

#[AsMapper(source: Tournament::class, destination: TournamentDTO::class)]
final class TournamentMapping implements MappingInterface
{
    /**
     * @param Tournament $source
     * @return TournamentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $mapper = $context['mapper'] ?? throw new InvalidArgumentException('Mapper is required in context');

        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
            status: $source->getStatus()->value,
            createdById: $source->getCreatedBy()?->getId(),
            seasonName: $source->getSeason()?->getName(),
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
            resultsHiddenUntil: $source->getResultsHiddenUntil(),
            registrationDeadline: $source->getRegistrationDeadline(),
            detailsHiddenUntil: $source->getDetailsHiddenUntil(),
            submissionDeadline: $source->getSubmissionDeadline(),
            toursCount: $source->getToursCount(),
            questionsPerTour: $source->getQuestionsPerTour(),
            difficulty: $source->getDifficulty(),
            trueDl: $source->getTrueDl(),
            teamCount: $context['teamCount'] ?? 0,
            sessionCount: $context['sessionCount'] ?? 0,
            officials: self::groupOfficials($mapper, $context['officials'] ?? []),
        );
    }

    /**
     * @param list<\App\Entity\TournamentOfficial> $officials
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
