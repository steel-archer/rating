<?php

declare(strict_types=1);

namespace App\Common\Security;

use Exception;

final class CspNonceGenerator
{
    private ?string $nonce = null;

    /**
     * @throws Exception
     */
    public function getNonce(): string
    {
        return $this->nonce ??= base64_encode(random_bytes(16));
    }
}
