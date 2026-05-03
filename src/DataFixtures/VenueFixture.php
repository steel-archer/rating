<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\Town;
use App\Entity\Venue;
use App\Entity\VenueRepresentative;
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
        'Галерея',
        "Кав'ярня",
        'Квіз-бар',
        'Клуб',
        'Лаунж',
        'Паб',
        'Хаб',
    ];

    /** @var array<int, list<int>> town index => list of venue indices */
    public static array $townVenueMap = [];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = count(TownFixture::TOWNS);
        $playerCount = TeamFixture::PLAYER_COUNT;
        $venueIndex = 0;

        for ($t = 0; $t < $townCount; $t++) {
            $town = $this->getReference("town_$t", Town::class);
            $count = $faker->numberBetween(1, 3);
            self::$townVenueMap[$t] = [];

            for ($i = 0; $i < $count; $i++) {
                $venue = new Venue();
                $venue->setName($faker->randomElement(self::VENUE_NAMES) . ' ' . $faker->lastName());
                $venue->setTown($town);
                $manager->persist($venue);
                $this->addReference("venue_$venueIndex", $venue);
                self::$townVenueMap[$t][] = $venueIndex;

                // 1-2 representatives per venue
                $repCount = $faker->numberBetween(1, 2);
                $usedPlayers = [];
                for ($r = 0; $r < $repCount; $r++) {
                    do {
                        $playerIndex = $faker->numberBetween(0, $playerCount - 1);
                    } while (isset($usedPlayers[$playerIndex]));
                    $usedPlayers[$playerIndex] = true;

                    $vr = new VenueRepresentative();
                    $vr->setVenue($venue);
                    $vr->setPlayer($this->getReference("player_$playerIndex", Player::class));
                    $manager->persist($vr);
                }

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
