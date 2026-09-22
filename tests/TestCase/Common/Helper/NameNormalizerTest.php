<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Common\Helper;

use App\Common\Helper\NameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameNormalizerTest extends TestCase
{
    #[DataProvider('apostropheProvider')]
    public function testNormalizeApostrophes(?string $input, ?string $expected): void
    {
        self::assertSame($expected, NameNormalizer::normalizeApostrophes($input));
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function apostropheProvider(): iterable
    {
        $canonical = "Ап\u{02BC}строфенко";

        yield 'null' => [null, null];
        yield 'empty string' => ['', ''];
        yield 'no apostrophe' => ['Шевченко', 'Шевченко'];
        yield 'ascii apostrophe' => ["Ап\u{0027}строфенко", $canonical];
        yield 'right single quotation mark' => ["Ап\u{2019}строфенко", $canonical];
        yield 'grave accent' => ["Ап\u{0060}строфенко", $canonical];
        yield 'canonical stays unchanged' => [$canonical, $canonical];
        yield 'multiple apostrophes' => ["Д\u{0027}Артан\u{2019}ян", "Д\u{02BC}Артан\u{02BC}ян"];
    }
}
