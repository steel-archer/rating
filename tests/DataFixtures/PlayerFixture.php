<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Entity\Player;
use App\Entity\Town;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class PlayerFixture extends Fixture implements DependentFixtureInterface
{
    private const array MALE_FIRST_NAMES = [
        'Адам', 'Андрій', 'Антон', 'Артем', 'Богдан',
        'Борис', 'Вадим', 'Валентин', 'Василь', 'Віктор',
        'Віталій', 'Владислав', 'Геннадій', 'Григорій', 'Данило',
        'Дмитро', 'Євген', 'Захар', 'Іван', 'Ігор',
        'Кирило', 'Костянтин', 'Леонід', 'Максим', 'Марко',
        'Матвій', 'Микола', 'Михайло', 'Назар', 'Нікіта',
        'Олег', 'Олександр', 'Остап', 'Павло', 'Петро',
        'Роман', 'Ростислав', 'Руслан', 'Сергій', 'Станіслав',
        'Степан', 'Тарас', 'Тимофій', 'Федір', 'Юрій',
    ];

    private const array FEMALE_FIRST_NAMES = [
        'Аліна', 'Анастасія', 'Анна', 'Валентина', 'Валерія',
        'Вероніка', 'Вікторія', 'Дарина', 'Діана', 'Євгенія',
        'Ірина', 'Карина', 'Катерина', 'Лариса', 'Леся',
        'Лідія', 'Людмила', 'Марина', 'Марія', 'Мирослава',
        'Надія', 'Наталія', 'Оксана', 'Олена', 'Ольга',
        'Поліна', 'Світлана', 'Соломія', 'Софія', 'Тетяна',
        'Уляна', 'Христина', 'Юлія', 'Яна', 'Ярослава',
    ];

    private const array LAST_NAMES_MALE = [
        'Бабенко', 'Бойко', 'Бондар', 'Бондаренко', 'Василенко',
        'Вовк', 'Гаврилюк', 'Гнатюк', 'Гончар', 'Гончаренко',
        'Грищенко', 'Данилюк', 'Демченко', 'Дмитренко', 'Довженко',
        'Дорошенко', 'Жук', 'Захарченко', 'Іваненко', 'Іванов',
        'Карпенко', 'Кириленко', 'Клименко', 'Коваленко', 'Коваль',
        'Козак', 'Козлов', 'Колесник', 'Кравченко', 'Кузьменко',
        'Левченко', 'Лисенко', 'Литвин', 'Литвиненко', 'Лук\'яненко',
        'Мазур', 'Макаренко', 'Марченко', 'Мельник', 'Мельниченко',
        'Михайленко', 'Мороз', 'Назаренко', 'Олійник', 'Остапенко',
        'Павленко', 'Панченко', 'Петренко', 'Пилипенко', 'Поліщук',
        'Пономаренко', 'Попов', 'Приходько', 'Романенко', 'Руденко',
        'Савченко', 'Сидоренко', 'Сірко', 'Ткаченко', 'Тимошенко',
        'Федоренко', 'Хоменко', 'Цимбалюк', 'Чернов', 'Шевченко',
        'Шевчук', 'Шульга', 'Щербак', 'Юрченко', 'Яковенко',
    ];

    private const array LAST_NAMES_FEMALE = [
        'Бабенко', 'Бойко', 'Бондар', 'Бондаренко', 'Василенко',
        'Вовк', 'Гаврилюк', 'Гнатюк', 'Гончар', 'Гончаренко',
        'Грищенко', 'Данилюк', 'Демченко', 'Дмитренко', 'Довженко',
        'Дорошенко', 'Жук', 'Захарченко', 'Іваненко', 'Іванова',
        'Карпенко', 'Кириленко', 'Клименко', 'Коваленко', 'Коваль',
        'Козак', 'Козлова', 'Колесник', 'Кравченко', 'Кузьменко',
        'Левченко', 'Лисенко', 'Литвин', 'Литвиненко', 'Лук\'яненко',
        'Мазур', 'Макаренко', 'Марченко', 'Мельник', 'Мельниченко',
        'Михайленко', 'Мороз', 'Назаренко', 'Олійник', 'Остапенко',
        'Павленко', 'Панченко', 'Петренко', 'Пилипенко', 'Поліщук',
        'Пономаренко', 'Попова', 'Приходько', 'Романенко', 'Руденко',
        'Савченко', 'Сидоренко', 'Сірко', 'Ткаченко', 'Тимошенко',
        'Федоренко', 'Хоменко', 'Цимбалюк', 'Чернова', 'Шевченко',
        'Шевчук', 'Шульга', 'Щербак', 'Юрченко', 'Яковенко',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $townCount = TownFixture::$townCount;

        for ($i = 0; $i < TeamFixture::PLAYER_COUNT; $i++) {
            $gender = $faker->randomElement(['male', 'female']);

            $player = new Player();
            $player->setLastName(self::lastName($faker, $gender));
            $player->setFirstName(self::firstName($faker, $gender));
            $player->setPatronymic(
                $faker->boolean(70)
                    ? self::patronymic($faker->randomElement(self::MALE_FIRST_NAMES), $gender)
                    : null,
            );
            $player->setTown($this->getReference('town_' . ($i % $townCount), Town::class));
            $manager->persist($player);
            $this->addReference("player_$i", $player);
        }

        $manager->flush();
    }

    private static function firstName(Generator $faker, string $gender): string
    {
        return $faker->randomElement(
            $gender === 'male' ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES,
        );
    }

    private static function lastName(Generator $faker, string $gender): string
    {
        return $faker->randomElement(
            $gender === 'male' ? self::LAST_NAMES_MALE : self::LAST_NAMES_FEMALE,
        );
    }

    private static function patronymic(string $fatherName, string $gender): string
    {
        $suffix = $gender === 'male' ? 'ович' : 'івна';

        if (str_ends_with($fatherName, 'ій')) {
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
