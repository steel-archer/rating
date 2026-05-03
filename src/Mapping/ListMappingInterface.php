<?php

namespace App\Mapping;

interface ListMappingInterface extends MappingInterface
{
    /** @return list<object> */
    public static function mapList(array $sources, string $destinationClass, array $context = []): array;
}
