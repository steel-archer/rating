<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Validator;

use App\Common\Validator\UkrainianName;
use App\Common\Validator\UkrainianNameValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<UkrainianNameValidator> */
class UkrainianNameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UkrainianNameValidator();
    }

    #[DataProvider('validValuesProvider')]
    public function testValidValues(?string $value): void
    {
        $this->validator->validate($value, new UkrainianName());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validValuesProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'simple last name' => ['Шевченко'];
        yield 'with hyphen' => ['Нечуй-Левицький'];
        yield 'with space' => ['Марія Заньковецька'];
        yield 'ascii apostrophe' => ["Ап\u{0027}строфенко"];
        yield 'modifier letter apostrophe' => ["Ап\u{02BC}строфенко"];
        yield 'right single quotation mark' => ["Ап\u{2019}строфенко"];
        yield 'grave accent' => ["Ап\u{0060}строфенко"];
        yield 'apostrophe mid-name' => ["В\u{02BC}ячеслав"];
    }

    #[DataProvider('invalidValuesProvider')]
    public function testInvalidValues(string $value): void
    {
        $this->validator->validate($value, new UkrainianName());

        $this->buildViolation('name.invalid_characters')->assertRaised();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValuesProvider(): iterable
    {
        yield 'latin characters' => ['Shevchenko'];
        yield 'html tags' => ['<script>'];
        yield 'digits' => ['Іван2'];
        yield 'special characters' => ['Іван!'];
        yield 'sql injection' => ["'; DROP TABLE --"];
    }
}
