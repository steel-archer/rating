<?php

declare(strict_types=1);

namespace App\Classic\Helper;

final class FractionalRanking
{
    /**
     * @param list<int> $sortedScoresDesc
     * @return array<int, float> score => fractional rank
     */
    public static function rank(array $sortedScoresDesc): array
    {
        $result = [];
        $position = 1;
        $total = count($sortedScoresDesc);

        while ($position <= $total) {
            $score = $sortedScoresDesc[$position - 1];
            $count = 0;

            while ($position + $count <= $total && $sortedScoresDesc[$position + $count - 1] === $score) {
                $count++;
            }

            $result[$score] = $position + ($count - 1) / 2;
            $position += $count;
        }

        return $result;
    }
}
