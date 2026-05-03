<?php

namespace App\Mapping;

interface ListMappingInterface extends MappingInterface
{
    /**
     * @return list<object>
     */
    public function mapList(array $sources, string $destinationClass, array $context = []): array;
}
