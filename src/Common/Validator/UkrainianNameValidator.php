<?php

declare(strict_types=1);

namespace App\Common\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UkrainianNameValidator extends ConstraintValidator
{
    /**
     * Allow the various apostrophe characters commonly produced by Ukrainian keyboards and
     * typographic input: ASCII apostrophe (U+0027), modifier letter apostrophe (U+02BC,
     * recommended by Ukrainian orthography), right single quotation mark (U+2019) and grave
     * accent (U+0060, sometimes typed by mistake).
     */
    public const string PATTERN = "/^[А-ЩЬЮЯЄІЇҐа-щьюяєіїґ'\x{02BC}\x{2019}\x{0060} -]+$/u";

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
