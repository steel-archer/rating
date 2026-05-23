<?php

declare(strict_types=1);

namespace App\Common\Mapping;

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
