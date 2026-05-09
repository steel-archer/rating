<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Validator\UkrainianTownName;
use App\Validator\UkrainianTownNameValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<UkrainianTownNameValidator> */
class UkrainianTownNameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UkrainianTownNameValidator();
    }

    #[DataProvider('validValuesProvider')]
    public function testValidValues(?string $value): void
    {
        $this->validator->validate($value, new UkrainianTownName());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validValuesProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'simple town' => ['Київ'];
        yield 'with hyphen' => ["Кам'янець-Подільський"];
        yield 'with space' => ['Біла Церква'];
        yield 'with parentheses' => ['Южноукраїнськ (Миколаїв. обл.)'];
        yield 'with comma' => ['Дніпро, лівий берег'];
        yield 'with dot' => ['м. Харків'];
    }

    #[DataProvider('invalidValuesProvider')]
    public function testInvalidValues(string $value): void
    {
        $this->validator->validate($value, new UkrainianTownName());

        $this->buildViolation('town.invalid_characters')->assertRaised();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValuesProvider(): iterable
    {
        yield 'latin characters' => ['Kyiv'];
        yield 'html tags' => ['<script>'];
        yield 'digits' => ['Район 5'];
        yield 'special characters' => ['Київ!'];
        yield 'sql injection' => ["'; DROP TABLE --"];
    }
}
