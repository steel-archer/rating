<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Helper;

use App\Classic\Helper\FractionalRanking;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FractionalRankingTest extends TestCase
{
    /**
     * @param list<int> $sortedScoresDesc
     * @param array<int, float> $expectedRanks
     */
    #[DataProvider('rankDataProvider')]
    public function testRank(array $sortedScoresDesc, array $expectedRanks): void
    {
        // Values may come back as int or float depending on whether a tie group
        // has an even or odd size (PHP's `/` returns int for exact divisions),
        // so we compare loosely and rely on floats in the expectations for intent.
        self::assertEquals($expectedRanks, FractionalRanking::rank($sortedScoresDesc));
    }

    /**
     * @return iterable<string, array{sortedScoresDesc: list<int>, expectedRanks: array<int, float>}>
     */
    public static function rankDataProvider(): iterable
    {
        yield 'empty input returns empty result' => [
            'sortedScoresDesc' => [],
            'expectedRanks' => [],
        ];

        yield 'single score gets rank one' => [
            'sortedScoresDesc' => [10],
            'expectedRanks' => [10 => 1.0],
        ];

        yield 'all distinct scores get sequential ranks' => [
            'sortedScoresDesc' => [30, 20, 10],
            'expectedRanks' => [30 => 1.0, 20 => 2.0, 10 => 3.0],
        ];

        yield 'tie at the top splits the first two ranks' => [
            'sortedScoresDesc' => [30, 30, 10],
            'expectedRanks' => [30 => 1.5, 10 => 3.0],
        ];

        yield 'tie in the middle splits the second and third ranks' => [
            'sortedScoresDesc' => [30, 20, 20, 10],
            'expectedRanks' => [30 => 1.0, 20 => 2.5, 10 => 4.0],
        ];

        yield 'tie at the bottom splits the last two ranks' => [
            'sortedScoresDesc' => [30, 20, 10, 10],
            'expectedRanks' => [30 => 1.0, 20 => 2.0, 10 => 3.5],
        ];

        yield 'all scores tied share the average rank' => [
            'sortedScoresDesc' => [10, 10, 10],
            'expectedRanks' => [10 => 2.0],
        ];
    }
}
