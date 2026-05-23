<?php

declare(strict_types=1);

namespace App\Common\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class UkrainianTownName extends Constraint
{
    public string $message = 'town.invalid_characters';
}
