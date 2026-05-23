<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\AppealDTO;
use App\Classic\Entity\Appeal;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: Appeal::class, destination: AppealDTO::class)]
final class AppealMapping implements MappingInterface
{
    /**
     * @param Appeal $source
     * @return AppealDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $answer = $source->getTournamentSessionTeamAnswer();

        return new $destinationClass(
            id: $source->getId(),
            type: $source->getType()->value,
            questionNumber: $answer->getQuestionNumber(),
            status: $source->getStatus()->value,
            text: $source->getText(),
            verdict: $source->getVerdict(),
        );
    }
}
