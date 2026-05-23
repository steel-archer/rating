<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Controller;

use App\Common\Attribute\RateLimited;
use Symfony\Component\HttpFoundation\Response;

#[RateLimited('upload')]
class RateLimitedUploadController
{
    public function __invoke(): Response
    {
        return new Response('OK');
    }
}
