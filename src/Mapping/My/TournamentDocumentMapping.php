<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\TournamentDocumentDTO;
use App\Entity\TournamentDocument;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentDocument::class, destination: TournamentDocumentDTO::class)]
final class TournamentDocumentMapping implements MappingInterface
{
    /**
     * @param TournamentDocument $source
     * @return TournamentDocumentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            originalName: $source->getOriginalName(),
            size: $source->getSize(),
            createdAt: $source->getCreatedAt(),
        );
    }
}
