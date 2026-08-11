<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Validator;

use App\Common\Validator\NoHtml;
use App\Common\Validator\NoHtmlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/** @extends ConstraintValidatorTestCase<NoHtmlValidator> */
class NoHtmlValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new NoHtmlValidator();
    }

    #[DataProvider('validValuesProvider')]
    public function testValidValues(?string $value): void
    {
        $this->validator->validate($value, new NoHtml());

        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validValuesProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'plain text' => ['some plain text'];
        yield 'ukrainian text' => ['Просто текст без розмітки'];
        yield 'whitespace only' => ['   '];
        yield 'text with angle bracket but no tag' => ['1 < 2 and 3 > 2'];
    }

    #[DataProvider('invalidValuesProvider')]
    public function testInvalidValues(string $value): void
    {
        $this->validator->validate($value, new NoHtml());

        $this->buildViolation('common.error')->assertRaised();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValuesProvider(): iterable
    {
        yield 'bold tag' => ['<b>bold</b>'];
        yield 'script tag' => ['<script>alert(1)</script>'];
        yield 'ukrainian text with tag' => ['<i>Курсив</i>'];
        yield 'self-closing tag' => ['line<br/>break'];
        yield 'tag surrounded by text' => ['before<div>middle</div>after'];
    }

    public function testUnexpectedConstraintTypeThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('some value', $this->createUnexpectedConstraint());
    }

    private function createUnexpectedConstraint(): Constraint
    {
        return new NotBlank();
    }
}
