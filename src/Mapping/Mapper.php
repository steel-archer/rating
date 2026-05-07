<?php

declare(strict_types=1);

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

            foreach ($reflection->getAttributes(AsMapper::class) as $attribute) {
                $asMapper = $attribute->newInstance();
                $key = $asMapper->source . '::' . $asMapper->destination;
                $this->registry[$key] = $mapping;
            }
        }
    }

    /**
     * @param object|array<string, mixed> $source
     * @param array<string, mixed> $context
     * @throws InvalidArgumentException
     */
    public function map(object|array $source, string $destinationClass, array $context = []): object
    {
        $sourceKey = is_array($source) ? 'array' : $source::class;
        $key = $sourceKey . '::' . $destinationClass;
        $mapping = $this->registry[$key]
            ?? throw new InvalidArgumentException("No mapping registered for $key");

        $context['mapper'] = $this;

        return $mapping->map($source, $destinationClass, $context);
    }

    /**
     * @param list<object|array<string, mixed>> $sources
     * @param array<string, mixed> $context
     * @return list<object>
     * @throws InvalidArgumentException
     */
    public function mapMultiple(array $sources, string $destinationClass, array $context = []): array
    {
        return array_map(
            fn(object|array $source) => $this->map($source, $destinationClass, $context),
            $sources,
        );
    }
}
