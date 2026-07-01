<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TournamentEditDTO;
use App\Classic\Entity\Tournament;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
            format: $source->getFormat()->value,
            onlineMode: $source->getOnlineMode()->value,
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
            discussionLink: $source->getDiscussionLink(),
            createdByPlayerId: $source->getCreatedBy()->getId(),
        );
    }
}
