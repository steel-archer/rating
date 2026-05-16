<?php

declare(strict_types=1);

namespace App\Mapping\My;

use App\DTO\Response\My\UploadedDocumentDTO;
use App\Entity\TournamentDocument;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

#[AsMapper(source: TournamentDocument::class, destination: UploadedDocumentDTO::class)]
final class UploadedDocumentMapping implements MappingInterface
{
    /**
     * @param TournamentDocument $source
     * @return UploadedDocumentDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        return new $destinationClass(
            id: $source->getId(),
            originalName: $source->getOriginalName(),
            size: $source->getSize(),
        );
    }
}
