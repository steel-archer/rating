<?php

namespace App\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsMapper
{
    public function __construct(
        public string $source,
        public string $destination,
    ) {
    }
}
