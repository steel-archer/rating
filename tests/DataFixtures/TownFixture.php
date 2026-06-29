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
            'classic_tournament_session_team_player',
            'classic_tournament_session_team',
            'classic_tournament_session',
            'classic_tournament_official',
            'classic_tournament',
            'classic_team_player',
            'classic_team',
            'common_venue_representative',
            'common_venue',
            'common_player_claim',
            ] as $table
        ) {
            $conn->executeStatement("DELETE FROM `$table`");
        }
        $conn->executeStatement('UPDATE common_user SET player_id = NULL');
        $conn->executeStatement('DELETE FROM common_player');

        $towns = $manager->getRepository(Town::class)->findBy([], ['name' => 'ASC']);
        self::$townCount = count($towns);

        foreach ($towns as $i => $town) {
            $this->addReference("town_$i", $town);
        }
    }
}
