<?php

declare(strict_types=1);

namespace App\Common\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UkrainianNameValidator extends ConstraintValidator
{
    public const string PATTERN = "/^[А-ЩЬЮЯЄІЇҐа-щьюяєіїґ' -]+$/u";

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UkrainianName) {
            throw new UnexpectedTypeException($constraint, UkrainianName::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!preg_match(self::PATTERN, $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
