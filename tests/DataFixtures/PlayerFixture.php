<?php

namespace App\Tests\DataFixtures;

use App\Entity\Player;
use App\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PlayerFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = count(TownFixture::TOWNS);

        for ($i = 0; $i < TeamFixture::PLAYER_COUNT; $i++) {
            $gender = $faker->randomElement(['male', 'female']);

            $player = new Player();
            $player->setLastName($faker->lastName());
            $player->setFirstName($faker->firstName($gender));
            $player->setPatronymic($faker->boolean(70) ? self::patronymic($faker->firstName('male'), $gender) : null);
            $player->setTown($this->getReference('town_' . ($i % $townCount), Town::class));
            $manager->persist($player);
            $this->addReference("player_$i", $player);
        }

        $manager->flush();
    }

    private static function patronymic(string $fatherName, string $gender): string
    {
        $suffix = $gender === 'male' ? 'ович' : 'івна';

        if (str_ends_with($fatherName, 'ій') || str_ends_with($fatherName, 'ій')) {
            $base = mb_substr($fatherName, 0, -2);
            return $base . ($gender === 'male' ? 'ійович' : 'іївна');
        }

        if (str_ends_with($fatherName, 'й')) {
            $base = mb_substr($fatherName, 0, -1);
            return $base . ($gender === 'male' ? 'йович' : 'ївна');
        }

        if (str_ends_with($fatherName, 'о')) {
            $base = mb_substr($fatherName, 0, -1);
            return $base . $suffix;
        }

        return $fatherName . $suffix;
    }

    public function getDependencies(): array
    {
        return [TownFixture::class];
    }
}
