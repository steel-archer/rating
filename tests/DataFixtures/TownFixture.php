<?php

namespace App\Tests\DataFixtures;

use App\Entity\Country;
use App\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class TownFixture extends Fixture
{
    public const array TOWNS = [
        'Вінниця',
        'Дніпро',
        'Запоріжжя',
        'Івано-Франківськ',
        'Київ',
        'Кропивницький',
        'Луцьк',
        'Миколаїв',
        'Одеса',
        'Полтава',
        'Рівне',
        'Суми',
        'Тернопіль',
        'Ужгород',
        'Харків',
        'Хмельницький',
        'Черкаси',
        'Чернівці',
        'Чернігів',
    ];

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);
        $conn = $manager->getConnection();
        foreach ([
            'tournament_session_team_player',
            'tournament_session_team',
            'tournament_session',
            'tournament_official',
            'tournament',
            'team_player',
            'team',
            'venue_representative',
            'venue',
            'player_claim',
            'player',
            'town',
        ] as $table) {
            $conn->executeStatement("DELETE FROM `$table`");
        }

        $ukraine = $manager->getRepository(Country::class)->find(1);

        if (!$ukraine) {
            $ukraine = new Country();
            $ukraine->setName('Україна');
            $manager->persist($ukraine);
            $manager->flush();
        }

        foreach (self::TOWNS as $i => $name) {
            $town = new Town();
            $town->setName($name);
            $town->setCountry($ukraine);
            $manager->persist($town);
            $this->addReference("town_$i", $town);
        }

        $manager->flush();
    }
}
