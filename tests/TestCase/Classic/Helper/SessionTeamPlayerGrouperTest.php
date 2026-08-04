<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Helper;

use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Classic\Helper\SessionTeamPlayerGrouper;
use PHPUnit\Framework\TestCase;

class SessionTeamPlayerGrouperTest extends TestCase
{
    public function testGroupReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], SessionTeamPlayerGrouper::group([]));
    }

    public function testGroupPutsAllPlayersOfOneTeamIntoASingleGroupPreservingOrder(): void
    {
        $playerOne = $this->createPlayerOnTeam(5);
        $playerTwo = $this->createPlayerOnTeam(5);
        $playerThree = $this->createPlayerOnTeam(5);

        $result = SessionTeamPlayerGrouper::group([$playerOne, $playerTwo, $playerThree]);

        self::assertSame([5 => [$playerOne, $playerTwo, $playerThree]], $result);
    }

    public function testGroupBucketsPlayersByTeamIdPreservingOrderWithinEachBucket(): void
    {
        $playerOnTeamOneA = $this->createPlayerOnTeam(1);
        $playerOnTeamTwo = $this->createPlayerOnTeam(2);
        $playerOnTeamOneB = $this->createPlayerOnTeam(1);
        $playerOnTeamThree = $this->createPlayerOnTeam(3);

        $result = SessionTeamPlayerGrouper::group([
            $playerOnTeamOneA,
            $playerOnTeamTwo,
            $playerOnTeamOneB,
            $playerOnTeamThree,
        ]);

        self::assertSame(
            [
                1 => [$playerOnTeamOneA, $playerOnTeamOneB],
                2 => [$playerOnTeamTwo],
                3 => [$playerOnTeamThree],
            ],
            $result,
        );
    }

    private function createPlayerOnTeam(int $teamId): TournamentSessionTeamPlayer
    {
        $team = $this->createMock(TournamentSessionTeam::class);
        $team->expects($this->once())->method('getId')->willReturn($teamId);

        $player = $this->createMock(TournamentSessionTeamPlayer::class);
        $player->expects($this->once())->method('getTournamentSessionTeam')->willReturn($team);

        return $player;
    }
}
