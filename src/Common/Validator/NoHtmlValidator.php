<?php

declare(strict_types=1);

namespace App\Common\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NoHtmlValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoHtml) {
            throw new UnexpectedTypeException($constraint, NoHtml::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if ($value !== strip_tags($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
