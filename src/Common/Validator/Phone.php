<?php

declare(strict_types=1);

namespace App\Common\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Phone extends Constraint
{
    public string $invalidMessage = 'contact.phone.invalid';
    public string $blockedCountryMessage = 'contact.phone.blocked_country';
}
