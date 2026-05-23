<?php

declare(strict_types=1);

namespace App\Classic\DTO\Response\My;

final readonly class UploadedDocumentDTO
{
    public function __construct(
        public int $id,
        public string $originalName,
        public int $size,
    ) {
    }
}
