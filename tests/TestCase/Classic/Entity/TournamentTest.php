<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Entity;

use App\Classic\Entity\Tournament;
use App\Classic\Enum\TournamentFormat;
use App\Classic\Enum\TournamentStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TournamentTest extends TestCase
{
    /**
     * @param 'null'|'past'|'future' $deadline
     */
    #[DataProvider('isPhaseOpenProvider')]
    public function testIsPhaseOpen(
        TournamentFormat $format,
        TournamentStatus $status,
        string $deadline,
        bool $expectedOpen,
    ): void {
        $deadlineValue = match ($deadline) {
            'null' => null,
            'past' => new DateTimeImmutable('-1 day'),
            'future' => new DateTimeImmutable('+1 day'),
        };

        $tournament = new Tournament();
        $tournament->setFormat($format);
        $tournament->setStatus($status);
        $tournament->setRegistrationDeadline($deadlineValue);
        $tournament->setSubmissionDeadline($deadlineValue);
        $tournament->setAppealDeadline($deadlineValue);

        self::assertSame($expectedOpen, $tournament->isRegistrationOpen());
        self::assertSame($expectedOpen, $tournament->isSubmissionOpen());
        self::assertSame($expectedOpen, $tournament->isAppealOpen());
    }

    /**
     * @return iterable<string, array{TournamentFormat, TournamentStatus, string, bool}>
     */
    public static function isPhaseOpenProvider(): iterable
    {
        yield 'distributed draft without deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Draft,
            'null',
            false,
        ];
        yield 'distributed draft with past deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Draft,
            'past',
            false,
        ];
        yield 'distributed draft with future deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Draft,
            'future',
            true,
        ];
        yield 'distributed published without deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Published,
            'null',
            false,
        ];
        yield 'distributed published with past deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Published,
            'past',
            false,
        ];
        yield 'distributed published with future deadline' => [
            TournamentFormat::Distributed,
            TournamentStatus::Published,
            'future',
            true,
        ];
        yield 'centralized draft without deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Draft,
            'null',
            false,
        ];
        yield 'centralized draft with past deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Draft,
            'past',
            false,
        ];
        yield 'centralized draft with future deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Draft,
            'future',
            false,
        ];
        yield 'centralized published without deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Published,
            'null',
            true,
        ];
        yield 'centralized published with past deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Published,
            'past',
            true,
        ];
        yield 'centralized published with future deadline' => [
            TournamentFormat::Centralized,
            TournamentStatus::Published,
            'future',
            true,
        ];
    }
}
