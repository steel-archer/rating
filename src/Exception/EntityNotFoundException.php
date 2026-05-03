<?php

namespace App\Exception;

use RuntimeException;

class EntityNotFoundException extends RuntimeException
{
    public static function forId(string $entity, int $id): self
    {
        return new self("$entity #$id not found");
    }
}
