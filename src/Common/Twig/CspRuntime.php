<?php

declare(strict_types=1);

namespace App\Common\Twig;

use App\Common\Security\CspNonceGenerator;
use Exception;
use Twig\Extension\RuntimeExtensionInterface;

class CspRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly CspNonceGenerator $nonceGenerator)
    {
    }

    /**
     * @throws Exception
     */
    public function getNonce(): string
    {
        return $this->nonceGenerator->getNonce();
    }
}
