<?php

declare(strict_types=1);

namespace App\Classic\Exception;

use LogicException;

final class TournamentValidationException extends LogicException
{
    /** @param list<string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(', ', $errors));
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
