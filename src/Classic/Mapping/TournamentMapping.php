<?php

declare(strict_types=1);

namespace App\Classic\Mapping;

use App\Common\Mapping\AsMapper;
use App\Common\Mapping\Mapper;
use App\Common\Mapping\MappingInterface;
use App\Classic\DTO\Response\Tournament\OfficialDTO;
use App\Classic\DTO\Response\TournamentDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentOfficial;
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
            format: $source->getFormat()->value,
            createdById: $source->getCreatedBy()?->getId(),
            seasonName: $source->getSeason()?->getName(),
            startedAt: $source->getStartedAt(),
            endedAt: $source->getEndedAt(),
            resultsHiddenUntil: $source->getResultsHiddenUntil(),
            registrationDeadline: $source->getRegistrationDeadline(),
            detailsHiddenUntil: $source->getDetailsHiddenUntil(),
            submissionDeadline: $source->getSubmissionDeadline(),
            appealDeadline: $source->getAppealDeadline(),
            toursCount: $source->getToursCount(),
            questionsPerTour: $source->getQuestionsPerTour(),
            difficulty: $source->getDifficulty(),
            trueDl: $source->getTrueDl(),
            discussionLink: $source->getDiscussionLink(),
            teamCount: $context['teamCount'] ?? 0,
            sessionCount: $context['sessionCount'] ?? 0,
            disputeCount: $context['disputeCount'] ?? 0,
            appealCount: $context['appealCount'] ?? 0,
            officials: self::groupOfficials($mapper, $context['officials'] ?? []),
        );
    }

    /**
     * @param list<TournamentOfficial> $officials
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
