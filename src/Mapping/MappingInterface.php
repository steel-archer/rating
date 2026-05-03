<?php

namespace App\Mapping;

interface MappingInterface
{
    public static function mapTo(mixed $source, string $destinationClass, array $context = []): object;
}
