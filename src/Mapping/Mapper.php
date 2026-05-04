<?php

namespace App\Mapping;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

final class Mapper
{
    /**
     * @var array<string, MappingInterface> "sourceClass::destinationClass" => mapping
     */
    private array $registry = [];

    /**
     * @param iterable<MappingInterface> $mappings
     * @throws ReflectionException
     */
    public function __construct(iterable $mappings)
    {
        foreach ($mappings as $mapping) {
            $reflection = new ReflectionClass($mapping);
            $attributes = $reflection->getAttributes(AsMapper::class);

            foreach ($attributes as $attribute) {
                $asMapper = $attribute->newInstance();
                $key = $asMapper->source . '::' . $asMapper->destination;
                $this->registry[$key] = $mapping;
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     * @throws InvalidArgumentException
     */
    public function map(object $source, string $destinationClass, array $context = []): object
    {
        $key = $source::class . '::' . $destinationClass;
        $mapping = $this->registry[$key]
            ?? throw new InvalidArgumentException("No mapping registered for $key");

        $context['mapper'] = $this;

        return $mapping->map($source, $destinationClass, $context);
    }
}
