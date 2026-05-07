<?php

declare(strict_types=1);

namespace App\Mapping;

interface MappingInterface
{
    /** @param array<string, mixed> $context */
    public function map(mixed $source, string $destinationClass, array $context = []): object;
}
