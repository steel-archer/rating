<?php

namespace App\Mapping;

interface MappingInterface
{
    public function map(mixed $source, string $destinationClass, array $context = []): object;
}
