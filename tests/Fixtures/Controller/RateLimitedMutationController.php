<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Controller;

use App\Attribute\RateLimited;
use Symfony\Component\HttpFoundation\Response;

#[RateLimited('mutation')]
class RateLimitedMutationController
{
    public function __invoke(): Response
    {
        return new Response('OK');
    }
}
