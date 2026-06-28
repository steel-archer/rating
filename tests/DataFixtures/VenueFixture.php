<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Common\Entity\Player;
use App\Common\Entity\Town;
use App\Common\Entity\Venue;
use App\Common\Entity\VenueRepresentative;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VenueFixture extends Fixture implements DependentFixtureInterface
{
    private const array VENUE_NAMES = [
        'Антикафе',
        'Арт-простір',
        'Бар',
        'Бістро',
        'Галерея',
        'Гастропаб',
        "Кав'ярня",
        'Квіз-бар',
        'Клуб',
        'Коворкінг',
        'Культурний центр',
        'Лаунж',
        'Лофт',
        'Паб',
        'Піцерія',
        'Ресторан',
        'Студія',
        'Тераса',
        'Хаб',
        'Чайна',
    ];

    /** @var array<int, list<int>> town index => list of venue indices */
    public static array $townVenueMap = [];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = TownFixture::$townCount;
        $playerCount = TeamFixture::PLAYER_COUNT;
        $venueIndex = 0;

        for ($townIndex = 0; $townIndex < $townCount; $townIndex++) {
            $town = $this->getReference("town_$townIndex", Town::class);
            $count = $faker->numberBetween(1, 3);
            self::$townVenueMap[$townIndex] = [];
            $usedNames = [];

            for ($i = 0; $i < $count; $i++) {
                do {
                    $name = $faker->randomElement(self::VENUE_NAMES) . ' «' . $faker->lastName() . '»';
                } while (isset($usedNames[$name]));
                $usedNames[$name] = true;

                $venue = new Venue();
                $venue->setName($name);
                $venue->setTown($town);
                $venue->setIsApproved(true);
                self::$townVenueMap[$townIndex][] = $venueIndex;

                // 1-2 representatives per venue; first one becomes createdBy
                $repCount = $faker->numberBetween(1, 2);
                $usedPlayers = [];
                for ($r = 0; $r < $repCount; $r++) {
                    do {
                        $playerIndex = $faker->numberBetween(0, $playerCount - 1);
                    } while (isset($usedPlayers[$playerIndex]));
                    $usedPlayers[$playerIndex] = true;

                    $player = $this->getReference("player_$playerIndex", Player::class);

                    if ($r === 0) {
                        $venue->setCreatedBy($player);
                    }

                    $representative = new VenueRepresentative();
                    $representative->setVenue($venue);
                    $representative->setPlayer($player);
                    $manager->persist($representative);
                }

                $manager->persist($venue);
                $this->addReference("venue_$venueIndex", $venue);

                $venueIndex++;
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TownFixture::class, PlayerFixture::class];
    }
}
