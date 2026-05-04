<?php

namespace App\Tests\DataFixtures;

use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use App\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use RuntimeException;

class TeamFixture extends Fixture implements DependentFixtureInterface
{
    public const int TEAM_COUNT = 100;
    public const int PLAYER_COUNT = 500;

    private const array ADJECTIVES = [
        'Веселі',
        'Відчайдушні',
        'Дикі',
        'Залізні',
        'Зоряні',
        'Крижані',
        'Лісові',
        'Мудрі',
        'Нічні',
        'Палкі',
        'Сонні',
        'Таємні',
        'Хитрі',
        'Шалені',
        'Швидкі',
    ];

    private const array NOUNS = [
        'Бджоли',
        'Борсуки',
        'Вовки',
        'Ворони',
        'Дракони',
        'Єноти',
        'Їжаки',
        'Кактуси',
        'Коти',
        'Леви',
        'Лиси',
        'Орли',
        'Сови',
        'Фенікси',
    ];

    /** @var array<int, list<int>> team index => list of player indices (season 1 squad) */
    public static array $teamSquads = [];

    /** @var array<int, list<int>> town index => list of player indices */
    public static array $townPlayers = [];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = count(TownFixture::TOWNS);
        $seasons = $manager->getRepository(Season::class)->findAll();
        if (count($seasons) < 2) {
            throw new RuntimeException('Need at least 2 seasons. Run seed.sql first.');
        }

        // Build town → players map
        self::$townPlayers = [];
        for ($p = 0; $p < self::PLAYER_COUNT; $p++) {
            self::$townPlayers[$p % $townCount][] = $p;
        }

        $used = [];
        $teamNames = [];
        while (count($teamNames) < self::TEAM_COUNT) {
            $name = $faker->randomElement(self::ADJECTIVES) . ' ' . $faker->randomElement(self::NOUNS);
            if (!isset($used[$name])) {
                $used[$name] = true;
                $teamNames[] = $name;
            }
        }

        // 80% both seasons, 10% season 1 only, 10% season 2 only
        $bothCount = (int)(self::TEAM_COUNT * 0.8);
        $s1OnlyCount = (int)(self::TEAM_COUNT * 0.1);

        // Track used players per season to avoid duplicates
        $usedInSeason = [[], []];

        foreach ($teamNames as $i => $name) {
            $townIndex = $i % $townCount;

            $team = new Team();
            $team->setName($name);
            $team->setTown($this->getReference("town_$townIndex", Town::class));
            $manager->persist($team);
            $this->addReference("team_$i", $team);

            if ($i < $bothCount) {
                $teamSeasons = [$seasons[0], $seasons[1]];
            } elseif ($i < $bothCount + $s1OnlyCount) {
                $teamSeasons = [$seasons[0]];
            } else {
                $teamSeasons = [$seasons[1]];
            }

            $squadSize = $faker->numberBetween(4, 6);
            $basePlayers = self::pickPlayers($faker, $townIndex, $townCount, $squadSize, $usedInSeason[0]);

            foreach ($basePlayers as $pi) {
                $usedInSeason[0][$pi] = true;
            }
            self::$teamSquads[$i] = $basePlayers;

            foreach ($teamSeasons as $seasonIndex => $season) {
                if ($seasonIndex === 0) {
                    $squad = $basePlayers;
                } else {
                    $squad = $basePlayers;
                    $swapCount = $faker->numberBetween(1, min(2, count($squad)));
                    $excluded = $usedInSeason[1];
                    foreach ($squad as $pi) {
                        $excluded[$pi] = true;
                    }
                    for ($s = 0; $s < $swapCount; $s++) {
                        $newPlayer = self::pickPlayers($faker, $townIndex, $townCount, 1, $excluded);
                        if ($newPlayer !== []) {
                            $excluded[$newPlayer[0]] = true;
                            $squad[$s] = $newPlayer[0];
                        }
                    }
                    foreach ($squad as $pi) {
                        $usedInSeason[1][$pi] = true;
                    }
                }

                $captainIndex = $faker->numberBetween(0, count($squad) - 1);

                foreach ($squad as $idx => $playerIndex) {
                    $teamPlayer = new TeamPlayer();
                    $teamPlayer->setTeam($team);
                    $teamPlayer->setPlayer($this->getReference("player_$playerIndex", Player::class));
                    $teamPlayer->setSeason($season);
                    $teamPlayer->setIsCaptain($idx === $captainIndex);
                    $manager->persist($teamPlayer);
                }
            }
        }

        $manager->flush();
    }

    /**
     * 80% from the home town, 20% from others.
     *
     * @return list<int>
     */
    /**
     * @param array<int, true> $excluded
     * @return list<int>
     */
    private static function pickPlayers(\Faker\Generator $faker, int $townIndex, int $townCount, int $count, array $excluded): array
    {
        $result = [];
        $used = $excluded;

        for ($i = 0; $i < $count; $i++) {
            $fromHome = $faker->boolean(80);
            $pool = $fromHome
                ? (self::$townPlayers[$townIndex] ?? [])
                : self::playersFromOtherTowns($townIndex, $townCount);

            $picked = self::pickOneFrom($faker, $pool, $used);

            // Fallback: if not found in preferred pool, try the other one
            if ($picked === null) {
                $pool = $fromHome
                    ? self::playersFromOtherTowns($townIndex, $townCount)
                    : (self::$townPlayers[$townIndex] ?? []);
                $picked = self::pickOneFrom($faker, $pool, $used);
            }

            if ($picked !== null) {
                $result[] = $picked;
                $used[$picked] = true;
            }
        }

        return $result;
    }

    /**
     * @param list<int> $pool
     * @param array<int, true> $excluded
     */
    private static function pickOneFrom(\Faker\Generator $faker, array $pool, array $excluded): ?int
    {
        $available = array_values(array_filter($pool, static fn(int $p) => !isset($excluded[$p])));
        if ($available === []) {
            return null;
        }

        return $faker->randomElement($available);
    }

    /**
     * @return list<int>
     */
    private static function playersFromOtherTowns(int $excludeTown, int $townCount): array
    {
        $result = [];
        for ($t = 0; $t < $townCount; $t++) {
            if ($t === $excludeTown) {
                continue;
            }
            foreach (self::$townPlayers[$t] ?? [] as $p) {
                $result[] = $p;
            }
        }

        return $result;
    }

    public function getDependencies(): array
    {
        return [PlayerFixture::class, TownFixture::class];
    }
}
