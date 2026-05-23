<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Common\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class TownFixture extends Fixture
{
    public static int $townCount = 0;

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);
        $conn = $manager->getConnection();
        foreach (
            [
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
            ] as $table
        ) {
            $conn->executeStatement("DELETE FROM `$table`");
        }
        $conn->executeStatement('UPDATE `user` SET player_id = NULL');
        $conn->executeStatement('DELETE FROM player');

        $towns = $manager->getRepository(Town::class)->findBy([], ['name' => 'ASC']);
        self::$townCount = count($towns);

        foreach ($towns as $i => $town) {
            $this->addReference("town_$i", $town);
        }
    }
}
