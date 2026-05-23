<?php

declare(strict_types=1);

namespace App\Classic\Mapping\My;

use App\Classic\DTO\Response\My\TournamentDocumentDTO;
use App\Classic\Entity\TournamentDocument;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

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
