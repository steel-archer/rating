<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Tournament;

use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: Tournament::class, destination: TournamentContextDTO::class)]
final class TournamentContextMapping implements MappingInterface
{
    /**
     * @param Tournament $source
     * @return TournamentContextDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            name: $source->getName(),
        );
    }
}
