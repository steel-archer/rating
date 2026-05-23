<?php

declare(strict_types=1);

namespace App\Common\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PhoneValidator extends ConstraintValidator
{
    private const array BLOCKED_REGIONS = ['RU', 'BY'];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Phone) {
            throw new UnexpectedTypeException($constraint, Phone::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $phoneNumber = $phoneUtil->parse($value);
        } catch (NumberParseException) {
            $this->context->buildViolation($constraint->invalidMessage)->addViolation();

            return;
        }

        if (!$phoneUtil->isValidNumber($phoneNumber)) {
            $this->context->buildViolation($constraint->invalidMessage)->addViolation();

            return;
        }

        $region = $phoneUtil->getRegionCodeForNumber($phoneNumber);

        if (in_array($region, self::BLOCKED_REGIONS, true)) {
            $this->context->buildViolation($constraint->blockedCountryMessage)->addViolation();
        }
    }
}
