<?php

declare(strict_types=1);

namespace App\Common\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityNotFoundException extends NotFoundHttpException
{
    public static function forId(string $entity, int $id): self
    {
        return new self("$entity #$id not found");
    }
}
