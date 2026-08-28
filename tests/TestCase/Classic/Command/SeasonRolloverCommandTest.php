<?php

declare(strict_types=1);

namespace App\Tests\TestCase\Classic\Command;

use App\Classic\Entity\TeamPlayer;
use App\Classic\Entity\TeamPlayerTransfer;
use App\Classic\Enum\TeamPlayerTransferType;
use App\Common\Entity\Season;
use App\Tests\FixturesTrait;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SeasonRolloverCommandTest extends KernelTestCase
{
    use FixturesTrait;

    private const array FIXTURES = [
        'Entity/base.yaml',
        'Entity/season_rollover.yaml',
    ];

    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('dataProvider')]
    public function testRollover(
        ?callable $arrange,
        array $input,
        int $expectedStatus,
        callable $afterCallback,
    ): void {
        self::bootKernel();
        $objects = self::loadFixtures(self::FIXTURES);

        if ($arrange !== null) {
            $arrange($objects);
        }

        $tester = self::runCommand($input);

        self::assertSame($expectedStatus, $tester->getStatusCode(), $tester->getDisplay());
        $afterCallback($tester, $objects);
    }

    /**
     * @return iterable<string, array>
     */
    public static function dataProvider(): iterable
    {
        yield 'creates next season with correct dates' => [
            'arrange' => null,
            'input' => [],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester) {
                self::assertStringContainsString('Creating next season: 2026-2027', $tester->getDisplay());

                $target = self::findTargetSeason();
                self::assertNotNull($target);
                self::assertEquals(new DateTimeImmutable('2026-10-01 00:00:00'), $target->getStartedAt());
                self::assertEquals(new DateTimeImmutable('2027-09-30 23:59:59'), $target->getEndedAt());
            },
        ];

        yield 'carries over only players who were in squad and played' => [
            'arrange' => null,
            'input' => [],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester, array $objects) {
                $target = self::findTargetSeason();
                self::assertNotNull($target);

                // team_alpha: both base-squad players played -> both carried over
                self::assertNotNull(self::findTeamPlayer($objects, 'player_shevchenko', 'team_alpha', $target));
                self::assertNotNull(self::findTeamPlayer($objects, 'player_franko', 'team_alpha', $target));

                // team_beta: captain (lesya) did not play -> not carried over;
                // extra_one and extra_two both played -> carried over
                self::assertNull(self::findTeamPlayer($objects, 'player_lesya', 'team_beta', $target));
                self::assertNotNull(self::findTeamPlayer($objects, 'player_extra_one', 'team_beta', $target));
                self::assertNotNull(self::findTeamPlayer($objects, 'player_extra_two', 'team_beta', $target));

                // team_gamma: nobody played -> team skipped entirely
                self::assertNull(self::findTeamPlayer($objects, 'player_kotsubynsky', 'team_gamma', $target));
            },
        ];

        yield 'keeps current captain when carried over' => [
            'arrange' => null,
            'input' => [],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester, array $objects) {
                $target = self::findTargetSeason();
                self::assertNotNull($target);

                $captain = self::findTeamPlayer($objects, 'player_shevchenko', 'team_alpha', $target);
                self::assertNotNull($captain);
                self::assertTrue($captain->isCaptain());

                $member = self::findTeamPlayer($objects, 'player_franko', 'team_alpha', $target);
                self::assertNotNull($member);
                self::assertFalse($member->isCaptain());
            },
        ];

        yield 'reassigns captaincy to most active player when captain drops out' => [
            'arrange' => null,
            'input' => [],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester, array $objects) {
                self::assertStringContainsString('Captaincy was reassigned', $tester->getDisplay());

                $target = self::findTargetSeason();
                self::assertNotNull($target);

                // extra_one played twice, extra_two once -> extra_one becomes captain
                $newCaptain = self::findTeamPlayer($objects, 'player_extra_one', 'team_beta', $target);
                self::assertNotNull($newCaptain);
                self::assertTrue($newCaptain->isCaptain());

                $notCaptain = self::findTeamPlayer($objects, 'player_extra_two', 'team_beta', $target);
                self::assertNotNull($notCaptain);
                self::assertFalse($notCaptain->isCaptain());
            },
        ];

        yield 'writes joined transfer dated at target season start' => [
            'arrange' => null,
            'input' => [],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester, array $objects) {
                $target = self::findTargetSeason();
                self::assertNotNull($target);

                $transfer = self::getContainer()->get('doctrine')
                    ->getRepository(TeamPlayerTransfer::class)
                    ->findOneBy([
                        'player' => $objects['player_shevchenko']->getId(),
                        'team' => $objects['team_alpha']->getId(),
                        'season' => $target->getId(),
                        'type' => TeamPlayerTransferType::Joined,
                    ]);

                self::assertNotNull($transfer);
                self::assertEquals($target->getStartedAt(), $transfer->getDate());
            },
        ];

        yield 'dry run writes nothing' => [
            'arrange' => null,
            'input' => ['--dry-run' => true],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester) {
                self::assertStringContainsString('DRY RUN', $tester->getDisplay());
                self::assertStringContainsString('no changes were written', $tester->getDisplay());

                self::assertNull(self::findTargetSeason());

                // Only the 6 base-squad rows from the fixture, nothing added.
                $teamPlayerCount = (int) self::getContainer()->get('doctrine')->getManager()
                    ->getRepository(TeamPlayer::class)
                    ->createQueryBuilder('tp')
                    ->select('COUNT(tp.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
                self::assertSame(6, $teamPlayerCount);
            },
        ];

        yield 'rerun is idempotent' => [
            'arrange' => static function () {
                self::runCommand(['--from' => 'season_current'])->assertCommandIsSuccessful();
            },
            'input' => ['--from' => 'season_current'],
            'expectedStatus' => 0,
            'afterCallback' => static function (CommandTester $tester) {
                self::assertStringContainsString('Next season already exists: 2026-2027', $tester->getDisplay());

                $target = self::findTargetSeason();
                self::assertNotNull($target);

                // Base squad (6) + first-run carried players; a second run must not
                // duplicate TeamPlayer rows or Joined transfers for the same season.
                self::assertSame(4, self::countInSeason(TeamPlayer::class, $target));
                self::assertSame(4, self::countInSeason(TeamPlayerTransfer::class, $target));
            },
        ];

        yield 'fails when source season not found' => [
            'arrange' => null,
            'input' => ['--from' => '999999'],
            'expectedStatus' => 1,
            'afterCallback' => static function (CommandTester $tester) {
                self::assertStringContainsString('not found', $tester->getDisplay());
            },
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function runCommand(array $input = []): CommandTester
    {
        // Resolve a season reference placeholder to its real id at run time.
        if (isset($input['--from']) && $input['--from'] === 'season_current') {
            $season = self::getContainer()->get('doctrine')
                ->getRepository(Season::class)
                ->findOneBy(['name' => '2025-2026']);
            $input['--from'] = (string) $season->getId();
        }

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:season:rollover'));
        $tester->execute($input);

        return $tester;
    }

    private static function findTargetSeason(): ?Season
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(Season::class)
            ->findOneBy(['name' => '2026-2027']);
    }

    /**
     * @param array<string, object> $objects
     */
    private static function findTeamPlayer(
        array $objects,
        string $playerRef,
        string $teamRef,
        Season $season,
    ): ?TeamPlayer {
        return self::getContainer()->get('doctrine')
            ->getRepository(TeamPlayer::class)
            ->findOneBy([
                'player' => $objects[$playerRef]->getId(),
                'team' => $objects[$teamRef]->getId(),
                'season' => $season->getId(),
            ]);
    }

    /**
     * @param class-string $entityClass
     */
    private static function countInSeason(string $entityClass, Season $season): int
    {
        return (int) self::getContainer()->get('doctrine')->getManager()
            ->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
