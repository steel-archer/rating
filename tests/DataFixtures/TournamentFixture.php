<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Entity\Player;
use App\Entity\Season;
use App\Entity\SessionClaim;
use App\Enum\SessionClaimStatus;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Enum\TournamentOfficialRole;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamAnswer;
use App\Entity\TournamentSessionTeamPlayer;
use App\Enum\TournamentStatus;
use App\Entity\Venue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use RuntimeException;

class TournamentFixture extends Fixture implements DependentFixtureInterface
{
    private const array NAMES = [
        'Битва розумів',
        'Весняний кубок',
        'Гран-прі',
        'Зимова серія',
        'Золота сова',
        'Інтелект-марафон',
        'Кубок Львова',
        'Кубок міста',
        'Ліга чемпіонів',
        'Нічна ліга',
        'Осінній бриз',
        'Турнір новачків',
        'Фінал сезону',
        'Чемпіонат України',
    ];

    private const array ONE_TIME_NAMES = [
        'Зоряні Леви',
        'Нічні Вовки',
        'Крижані Дракони',
        'Вогняні Фенікси',
        'Срібні Соколи',
        'Тіньові Рисі',
        'Палкі Орли',
        'Шалені Коти',
        'Хоробрі Грифони',
        'Магічні Сови',
        'Дикі Бізони',
        'Стрімкі Гепарди',
        'Яскраві Химери',
        'Безстрашні Козаки',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $seasons = $manager->getRepository(Season::class)->findAll();
        $season = $seasons[0] ?? throw new RuntimeException('No seasons found');

        $townCount = TownFixture::$townCount;
        $teamCount = TeamFixture::TEAM_COUNT;
        $playerCount = TeamFixture::PLAYER_COUNT;

        // town → team indices
        $townTeams = [];
        for ($t = 0; $t < $teamCount; $t++) {
            $townTeams[$t % $townCount][] = $t;
        }

        foreach (self::NAMES as $i => $name) {
            $tournament = new Tournament();
            $tournament->setName($name);
            $tournament->setSeason($season);
            $month = str_pad((string)(($i % 3) + 10), 2, '0', STR_PAD_LEFT);
            $day = str_pad((string)($faker->numberBetween(1, 28)), 2, '0', STR_PAD_LEFT);
            $startDate = (new DateTimeImmutable("2024-$month-$day"))->setTime(0, 0, 0);
            $endDate = $startDate->modify('+' . $faker->numberBetween(7, 14) . ' days')->setTime(23, 59, 59);
            $tournament->setStartedAt($startDate);
            $tournament->setEndedAt($endDate);
            $tournament->setResultsHiddenUntil($endDate->modify('+1 day')->setTime(0, 0, 0));
            $tournament->setRegistrationDeadline($endDate->modify('-1 day')->setTime(23, 59, 59));
            $tournament->setDetailsHiddenUntil($endDate->modify('+3 days')->setTime(0, 0, 0));
            $tournament->setSubmissionDeadline($endDate->modify('+2 days')->setTime(23, 59, 59));
            $tournament->setStatus(TournamentStatus::Published);
            $tournament->setToursCount($faker->boolean(90) ? 3 : $faker->randomElement([4, 5]));
            $tournament->setQuestionsPerTour($faker->boolean(90) ? 12 : $faker->randomElement([13, 14, 15]));
            $tournament->setDifficulty($faker->randomFloat(1, 2, 5));
            $tournament->setTrueDl($faker->randomFloat(2, 1.5, 4.5));
            $manager->persist($tournament);

            // Officials
            $usedOfficials = [];
            foreach (TournamentOfficialRole::cases() as $role) {
                $count = $faker->numberBetween(1, 3);
                for ($k = 0; $k < $count; $k++) {
                    do {
                        $playerIndex = $faker->numberBetween(0, $playerCount - 1);
                        $key = $playerIndex . '_' . $role->value;
                    } while (isset($usedOfficials[$key]));
                    $usedOfficials[$key] = true;

                    $official = new TournamentOfficial();
                    $official->setTournament($tournament);
                    $official->setPlayer($this->getReference("player_$playerIndex", Player::class));
                    $official->setRole($role);
                    $manager->persist($official);
                }
            }

            // Track uniqueness per tournament
            $usedTeams = [];
            $usedPlayers = [];
            $oneTimeNameAssigned = false;

            // Sessions — random subset of towns
            $sessionTownCount = $faker->numberBetween(5, min(13, $townCount));
            $sessionTowns = $faker->randomElements(range(0, $townCount - 1), $sessionTownCount);
            $totalQuestions = $tournament->getToursCount() * $tournament->getQuestionsPerTour();

            foreach ($sessionTowns as $townIndex) {
                $venueIndices = VenueFixture::$townVenueMap[$townIndex] ?? [];
                if ($venueIndices === []) {
                    continue;
                }

                $venueIndex = $faker->randomElement($venueIndices);

                $session = new TournamentSession();
                $session->setTournament($tournament);
                $session->setVenue($this->getReference("venue_$venueIndex", Venue::class));
                $session->setRepresentative($this->getReference('player_' . $faker->numberBetween(0, $playerCount - 1), Player::class));
                $session->setHost($this->getReference('player_' . $faker->numberBetween(0, $playerCount - 1), Player::class));
                $session->setPlayedAt(new DateTimeImmutable("2024-$month-$day 19:00"));
                $manager->persist($session);

                $claim = new SessionClaim();
                $claim->setSession($session);
                $claim->setPlayer($session->getRepresentative());
                $claim->setStatus(SessionClaimStatus::Approved);
                $claim->setResolvedAt(new DateTimeImmutable("2024-$month-$day"));
                $manager->persist($claim);

                // Teams from this town (skip already used in this tournament)
                $teams = $townTeams[$townIndex] ?? [];
                foreach ($teams as $teamIndex) {
                    if (isset($usedTeams[$teamIndex])) {
                        continue;
                    }
                    $usedTeams[$teamIndex] = true;

                    $teamTownIndex = $teamIndex % $townCount;

                    $sessionTeam = new TournamentSessionTeam();
                    $sessionTeam->setTournamentSession($session);
                    $sessionTeam->setTeam($this->getReference("team_$teamIndex", Team::class));
                    $sessionTeam->setResultsSubmitted(true);
                    $manager->persist($sessionTeam);

                    $correctProbability = $faker->numberBetween(20, 85);
                    for ($q = 1; $q <= $totalQuestions; $q++) {
                        $answer = new TournamentSessionTeamAnswer();
                        $answer->setTournamentSessionTeam($sessionTeam);
                        $answer->setQuestionNumber($q);
                        $answer->setIsCorrect($faker->boolean($correctProbability));
                        $sessionTeam->getAnswers()->add($answer);
                        $manager->persist($answer);
                    }

                    $sessionTeam->recalculateScore();

                    // Base squad + legionaries if needed
                    $baseSquad = TeamFixture::$teamSquads[$teamIndex] ?? [];

                    // Assign one-time name to first team with base squad per tournament
                    if (!$oneTimeNameAssigned && $baseSquad !== []) {
                        $sessionTeam->setOneTimeName(self::ONE_TIME_NAMES[$i]);
                        $oneTimeNameAssigned = true;
                    }

                    $squad = [];
                    $squadPlayerIds = [];

                    foreach ($baseSquad as $playerIndex) {
                        if (isset($usedPlayers[$playerIndex]) || isset($squadPlayerIds[$playerIndex])) {
                            continue;
                        }
                        $squad[] = ['player' => $playerIndex, 'legionary' => false];
                        $squadPlayerIds[$playerIndex] = true;
                    }

                    // Fill up to 4 players with legionaries (80% from same town)
                    $targetSize = max(4, count($squad));
                    $attempts = 0;
                    while (count($squad) < $targetSize && $attempts < 100) {
                        $attempts++;
                        $legIndex = self::pickLegionary($faker, $teamTownIndex, $townCount, $playerCount);
                        if (!isset($usedPlayers[$legIndex]) && !isset($squadPlayerIds[$legIndex])) {
                            $squad[] = ['player' => $legIndex, 'legionary' => true];
                            $squadPlayerIds[$legIndex] = true;
                        }
                    }

                    // 20% chance for an extra legionary
                    if ($faker->boolean(20)) {
                        $attempts = 0;
                        do {
                            $legIndex = self::pickLegionary($faker, $teamTownIndex, $townCount, $playerCount);
                            $attempts++;
                        } while ((isset($usedPlayers[$legIndex]) || isset($squadPlayerIds[$legIndex])) && $attempts < 50);

                        if (!isset($usedPlayers[$legIndex]) && !isset($squadPlayerIds[$legIndex])) {
                            $squad[] = ['player' => $legIndex, 'legionary' => true];
                            $squadPlayerIds[$legIndex] = true;
                        }
                    }

                    foreach ($squad as $entry) {
                        $usedPlayers[$entry['player']] = true;

                        $sessionTeamPlayer = new TournamentSessionTeamPlayer();
                        $sessionTeamPlayer->setTournamentSessionTeam($sessionTeam);
                        $sessionTeamPlayer->setPlayer($this->getReference("player_{$entry['player']}", Player::class));
                        $sessionTeamPlayer->setIsLegionary($entry['legionary']);
                        $manager->persist($sessionTeamPlayer);
                    }
                }
            }
        }

        $manager->flush();
    }

    /**
     * 80% from the team's town, 20% random.
     */
    private static function pickLegionary(\Faker\Generator $faker, int $teamTownIndex, int $townCount, int $playerCount): int
    {
        if ($faker->boolean(80)) {
            $townPlayers = TeamFixture::$townPlayers[$teamTownIndex] ?? [];
            if ($townPlayers !== []) {
                return $faker->randomElement($townPlayers);
            }
        }

        return $faker->numberBetween(0, $playerCount - 1);
    }

    public function getDependencies(): array
    {
        return [TeamFixture::class, VenueFixture::class];
    }
}
