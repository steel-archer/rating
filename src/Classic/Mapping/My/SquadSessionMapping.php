<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\SquadSessionDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
