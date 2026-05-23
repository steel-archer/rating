<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\ResultsSessionDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSession::class, destination: ResultsSessionDTO::class)]
final class ResultsSessionMapping implements MappingInterface
{
    /**
     * @param TournamentSession $source
     * @return ResultsSessionDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $tournament = $source->getTournament();

        return new $destinationClass(
            id: $source->getId(),
            toursCount: $tournament->getToursCount() ?? 0,
            questionsPerTour: $tournament->getQuestionsPerTour() ?? 0,
        );
    }
}
