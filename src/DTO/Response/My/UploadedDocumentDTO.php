<?php

declare(strict_types=1);

namespace App\DTO\Response\My;

final readonly class UploadedDocumentDTO
{
    public function __construct(
        public int $id,
        public string $originalName,
        public int $size,
    ) {
    }
}
