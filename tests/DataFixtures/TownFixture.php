<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Common\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class TownFixture extends Fixture
{
    public static int $townCount = 0;

    public function load(ObjectManager $manager): void
    {
        /**
         * Towns (and countries/seasons) are seeded by migrations, which are the
         * source of truth for reference data. This fixture only reads them and
         * exposes references for the dependent fixtures.
         */
        $towns = $manager->getRepository(Town::class)->findBy([], ['name' => 'ASC']);
        if ($towns === []) {
            throw new RuntimeException('No towns found. Run migrations before loading fixtures.');
        }

        self::$townCount = count($towns);

        foreach ($towns as $i => $town) {
            $this->addReference("town_$i", $town);
        }
    }
}
