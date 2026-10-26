<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Enum;

use App\Classic\Enum\SessionClaimWorkState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SessionClaimWorkStateTest extends TestCase
{
    /**
     * @param SessionClaimWorkState $state
     * @param bool $expected
     */
    #[DataProvider('isCurrentProvider')]
    public function testIsCurrent(SessionClaimWorkState $state, bool $expected): void
    {
        self::assertSame($expected, $state->isCurrent());
    }

    /**
     * @return iterable<string, array{state: SessionClaimWorkState, expected: bool}>
     */
    public static function isCurrentProvider(): iterable
    {
        yield 'awaiting moderation is current' => [
            'state' => SessionClaimWorkState::AwaitingModeration,
            'expected' => true,
        ];
        yield 'rejected is current' => [
            'state' => SessionClaimWorkState::Rejected,
            'expected' => true,
        ];
        yield 'awaiting game is current' => [
            'state' => SessionClaimWorkState::AwaitingGame,
            'expected' => true,
        ];
        yield 'results needed is current' => [
            'state' => SessionClaimWorkState::ResultsNeeded,
            'expected' => true,
        ];
        yield 'closed is not current' => [
            'state' => SessionClaimWorkState::Closed,
            'expected' => false,
        ];
    }

    /**
     * The "current" block orders actionable states first. This asserts the exact
     * ascending order so a reordering of the enum cases cannot silently change
     * how the list is sorted.
     */
    public function testSortPriorityOrdersActionableStatesFirst(): void
    {
        $states = [
            SessionClaimWorkState::Closed,
            SessionClaimWorkState::Rejected,
            SessionClaimWorkState::AwaitingGame,
            SessionClaimWorkState::AwaitingModeration,
            SessionClaimWorkState::ResultsNeeded,
        ];

        usort(
            $states,
            static fn(SessionClaimWorkState $a, SessionClaimWorkState $b): int => $a->sortPriority() <=> $b->sortPriority(),
        );

        self::assertSame(
            [
                SessionClaimWorkState::ResultsNeeded,
                SessionClaimWorkState::AwaitingModeration,
                SessionClaimWorkState::AwaitingGame,
                SessionClaimWorkState::Rejected,
                SessionClaimWorkState::Closed,
            ],
            $states,
        );
    }
}
