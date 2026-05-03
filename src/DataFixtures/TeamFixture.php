<?php

namespace App\DataFixtures;

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

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = count(TownFixture::TOWNS);
        $seasons = $manager->getRepository(Season::class)->findAll();
        if (count($seasons) < 2) {
            throw new RuntimeException('Need at least 2 seasons. Run seed.sql first.');
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

        $playerPool = range(0, self::PLAYER_COUNT - 1);
        $faker->shuffleArray($playerPool);
        $poolOffset = 0;

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

            // Base roster for first season
            $rosterSize = $faker->numberBetween(4, 6);
            $basePlayers = [];
            for ($j = 0; $j < $rosterSize; $j++) {
                $basePlayers[] = $playerPool[$poolOffset % self::PLAYER_COUNT];
                $poolOffset++;
            }
            self::$teamSquads[$i] = $basePlayers;

            foreach ($teamSeasons as $si => $season) {
                if ($si === 0) {
                    $roster = $basePlayers;
                } else {
                    // Between seasons: swap 1-2 players
                    $roster = $basePlayers;
                    $swapCount = $faker->numberBetween(1, min(2, count($roster)));
                    for ($s = 0; $s < $swapCount; $s++) {
                        $roster[$s] = $playerPool[$poolOffset % self::PLAYER_COUNT];
                        $poolOffset++;
                    }
                }

                $captainIndex = $faker->numberBetween(0, count($roster) - 1);

                foreach ($roster as $ri => $playerIndex) {
                    $tp = new TeamPlayer();
                    $tp->setTeam($team);
                    $tp->setPlayer($this->getReference("player_$playerIndex", Player::class));
                    $tp->setSeason($season);
                    $tp->setIsCaptain($ri === $captainIndex);
                    $manager->persist($tp);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PlayerFixture::class, TownFixture::class];
    }
}
