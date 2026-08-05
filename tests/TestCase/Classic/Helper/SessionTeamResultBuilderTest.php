<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Helper;

use App\Classic\Entity\Team;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Classic\Helper\SessionTeamResultBuilder;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TeamPlayerTransferRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use App\Common\Entity\Season;
use App\Common\Mapping\Mapper;
use App\Tests\Fixtures\Mapping\RecordingSessionTeamMapping;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class SessionTeamResultBuilderTest extends TestCase
{
    public function testBuildReturnsEmptyArrayAndNeverTouchesCollaboratorsForEmptyInput(): void
    {
        $sessionTeamRepository = $this->createMock(TournamentSessionTeamRepository::class);
        $sessionTeamRepository->expects($this->never())->method('getPlacesInTournament');

        $sessionTeamPlayerRepository = $this->createMock(TournamentSessionTeamPlayerRepository::class);
        $sessionTeamPlayerRepository->expects($this->never())->method('findBySessionTeamIds');

        $teamPlayerRepository = $this->createMock(TeamPlayerRepository::class);
        $teamPlayerRepository->expects($this->never())->method('getSquadMapBySeason');

        $transferRepository = $this->createMock(TeamPlayerTransferRepository::class);
        $transferRepository->expects($this->never())->method('findAllBySeason');
        $transferRepository->expects($this->never())->method('resolveSquadFromTransfers');

        $mapping = new RecordingSessionTeamMapping();
        $mapper = new Mapper([$mapping]);

        $builder = new SessionTeamResultBuilder(
            $sessionTeamRepository,
            $sessionTeamPlayerRepository,
            $teamPlayerRepository,
            $transferRepository,
            $mapper,
        );

        self::assertSame([], $builder->build([], null));
        self::assertSame([], $mapping->calls);
    }

    public function testBuildWiresPlaceSessionPlacePlayersAndSquadInfoIntoMapperContext(): void
    {
        $playedAt = new DateTimeImmutable('2026-01-15');

        $teamOne = $this->createTeam(11);
        $teamTwo = $this->createTeam(12);

        $sessionOne = $this->createSession($playedAt);
        $sessionTwo = $this->createSession(null);

        $sessionTeamOne = $this->createSessionTeam(101, $teamOne, $sessionOne, score: 30);
        $sessionTeamTwo = $this->createSessionTeam(102, $teamTwo, $sessionTwo, score: 20);
        $sessionTeams = [$sessionTeamOne, $sessionTeamTwo];

        $playerOnTeamOne = $this->createMock(TournamentSessionTeamPlayer::class);
        $playerOnTeamOne->expects($this->once())->method('getTournamentSessionTeam')->willReturn($sessionTeamOne);

        $season = new Season();
        self::setId($season, 1);
        $season->setName('2025/2026');

        $sessionTeamPlayerRepository = $this->createMock(TournamentSessionTeamPlayerRepository::class);
        $sessionTeamPlayerRepository->expects($this->once())
            ->method('findBySessionTeamIds')
            ->with([101, 102])
            ->willReturn([$playerOnTeamOne]);

        $sessionTeamRepository = $this->createMock(TournamentSessionTeamRepository::class);
        $sessionTeamRepository->expects($this->once())
            ->method('getPlacesInTournament')
            ->with([101, 102])
            ->willReturn([101 => 5.0, 102 => 7.0]);

        $teamPlayerRepository = $this->createMock(TeamPlayerRepository::class);
        $teamPlayerRepository->expects($this->once())
            ->method('getSquadMapBySeason')
            ->with($season)
            ->willReturn([
                11 => ['playerIds' => [201, 202], 'captainId' => 201],
                12 => ['playerIds' => [301], 'captainId' => null],
            ]);

        $transferRepository = $this->createMock(TeamPlayerTransferRepository::class);
        $transferRepository->expects($this->once())
            ->method('findAllBySeason')
            ->with($season)
            ->willReturn([]);
        $transferRepository->expects($this->once())
            ->method('resolveSquadFromTransfers')
            ->with([], [11 => ['team' => $teamOne, 'dates' => [$playedAt]]])
            ->willReturn([11 => ['2026-01-15' => [401, 402]]]);

        $mapping = new RecordingSessionTeamMapping();
        $mapper = new Mapper([$mapping]);

        $builder = new SessionTeamResultBuilder(
            $sessionTeamRepository,
            $sessionTeamPlayerRepository,
            $teamPlayerRepository,
            $transferRepository,
            $mapper,
        );

        $result = $builder->build($sessionTeams, $season);

        self::assertCount(2, $mapping->calls);

        self::assertSame($sessionTeamOne, $mapping->calls[0]['source']);
        self::assertSame(5.0, $mapping->calls[0]['context']['place']);
        // FractionalRanking::rank() returns int instead of float for odd-sized
        // tie groups (here: two singleton groups), so compare loosely by value.
        self::assertEquals(1.0, $mapping->calls[0]['context']['sessionPlace']);
        self::assertSame([$playerOnTeamOne], $mapping->calls[0]['context']['players']);
        self::assertSame(
            ['playerIds' => [401, 402], 'captainId' => 201],
            $mapping->calls[0]['context']['squadInfo'],
        );

        self::assertSame($sessionTeamTwo, $mapping->calls[1]['source']);
        self::assertSame(7.0, $mapping->calls[1]['context']['place']);
        self::assertEquals(2.0, $mapping->calls[1]['context']['sessionPlace']);
        self::assertSame([], $mapping->calls[1]['context']['players']);
        self::assertSame(
            ['playerIds' => [], 'captainId' => null],
            $mapping->calls[1]['context']['squadInfo'],
        );

        self::assertCount(2, $result);
        self::assertSame(101, $result[0]->sessionTeamId);
        self::assertSame(102, $result[1]->sessionTeamId);
    }

    public function testBuildDeduplicatesDatesWhenSameTeamPlaysTwoSessionsOnSameDay(): void
    {
        $playedAt = new DateTimeImmutable('2026-03-10');

        $team = $this->createTeam(50);
        $session = $this->createSession($playedAt);

        // Same team, same date, two different session-teams
        $sessionTeamA = $this->createSessionTeam(201, $team, $session, score: 25);
        $sessionTeamB = $this->createSessionTeam(202, $team, $session, score: 25);
        $sessionTeams = [$sessionTeamA, $sessionTeamB];

        $season = new Season();
        self::setId($season, 2);
        $season->setName('2025/2026');

        $sessionTeamPlayerRepository = $this->createMock(TournamentSessionTeamPlayerRepository::class);
        $sessionTeamPlayerRepository->expects($this->once())
            ->method('findBySessionTeamIds')
            ->with([201, 202])
            ->willReturn([]);

        $sessionTeamRepository = $this->createMock(TournamentSessionTeamRepository::class);
        $sessionTeamRepository->expects($this->once())
            ->method('getPlacesInTournament')
            ->with([201, 202])
            ->willReturn([201 => 1.0, 202 => 1.0]);

        $teamPlayerRepository = $this->createMock(TeamPlayerRepository::class);
        $teamPlayerRepository->expects($this->once())
            ->method('getSquadMapBySeason')
            ->with($season)
            ->willReturn([50 => ['playerIds' => [501], 'captainId' => 501]]);

        $transferRepository = $this->createMock(TeamPlayerTransferRepository::class);
        $transferRepository->expects($this->once())
            ->method('findAllBySeason')
            ->with($season)
            ->willReturn([]);
        // The key assertion: dates array should contain only ONE entry despite two session-teams
        $transferRepository->expects($this->once())
            ->method('resolveSquadFromTransfers')
            ->with([], [50 => ['team' => $team, 'dates' => [$playedAt]]])
            ->willReturn([50 => ['2026-03-10' => [501]]]);

        $mapping = new RecordingSessionTeamMapping();
        $mapper = new Mapper([$mapping]);

        $builder = new SessionTeamResultBuilder(
            $sessionTeamRepository,
            $sessionTeamPlayerRepository,
            $teamPlayerRepository,
            $transferRepository,
            $mapper,
        );

        $result = $builder->build($sessionTeams, $season);

        self::assertCount(2, $result);
        // Both session-teams get the same squad info from the deduplicated date
        self::assertSame(
            ['playerIds' => [501], 'captainId' => 501],
            $mapping->calls[0]['context']['squadInfo'],
        );
        self::assertSame(
            ['playerIds' => [501], 'captainId' => 501],
            $mapping->calls[1]['context']['squadInfo'],
        );
    }

    private function createTeam(int $id): Team
    {
        $team = new Team();
        self::setId($team, $id);

        return $team;
    }

    private function createSession(?DateTimeImmutable $playedAt): TournamentSession
    {
        return (new TournamentSession())->setPlayedAt($playedAt);
    }

    private function createSessionTeam(int $id, Team $team, TournamentSession $session, int $score): TournamentSessionTeam
    {
        $sessionTeam = (new TournamentSessionTeam())
            ->setTeam($team)
            ->setTournamentSession($session)
            ->setScore($score);
        self::setId($sessionTeam, $id);

        return $sessionTeam;
    }

    private static function setId(object $entity, int $id): void
    {
        (new ReflectionProperty($entity, 'id'))->setValue($entity, $id);
    }
}
