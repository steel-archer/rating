<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\SquadSessionDTO;
use App\Entity\TournamentSession;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentSession::class, destination: SquadSessionDTO::class)]
final class SquadSessionMapping implements MappingInterface
{
    /**
     * @param TournamentSession $source
     * @return SquadSessionDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            venueName: $source->getVenue()->getName(),
        );
    }
}
