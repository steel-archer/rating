<?php

declare(strict_types=1);

namespace App\Common\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RateLimited
{
    public function __construct(public string $limiter)
    {
    }
}
