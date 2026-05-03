<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentOfficial;
use App\Entity\TournamentOfficialRole;
use App\Entity\TournamentSession;
use App\Entity\TournamentSessionTeam;
use App\Entity\TournamentSessionTeamPlayer;
use App\Entity\Venue;
use DateTime;
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

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('uk_UA');
        $seasons = $manager->getRepository(Season::class)->findAll();
        $season = $seasons[0] ?? throw new RuntimeException('No seasons found');

        $townCount = count(TownFixture::TOWNS);
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
            $startDate = new DateTime("2024-$month-$day");
            $endDate = (clone $startDate)->modify('+' . $faker->numberBetween(7, 14) . ' days');
            $tournament->setStartedAt($startDate);
            $tournament->setEndedAt($endDate);
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

                    $off = new TournamentOfficial();
                    $off->setTournament($tournament);
                    $off->setPlayer($this->getReference("player_$playerIndex", Player::class));
                    $off->setRole($role);
                    $manager->persist($off);
                }
            }

            // Track uniqueness per tournament
            $usedTeams = [];
            $usedPlayers = [];

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
                $session->setPlayedAt(new DateTime("2024-$month-$day 19:00"));
                $manager->persist($session);

                // Teams from this town (skip already used in this tournament)
                $teams = $townTeams[$townIndex] ?? [];
                foreach ($teams as $teamIndex) {
                    if (isset($usedTeams[$teamIndex])) {
                        continue;
                    }
                    $usedTeams[$teamIndex] = true;

                    $st = new TournamentSessionTeam();
                    $st->setTournamentSession($session);
                    $st->setTeam($this->getReference("team_$teamIndex", Team::class));
                    $st->setScore($faker->numberBetween((int)($totalQuestions * 0.2), (int)($totalQuestions * 0.85)));
                    $manager->persist($st);

                    // Roster: base roster + legionaries if needed
                    $baseSquad = TeamFixture::$teamSquads[$teamIndex] ?? [];
                    $roster = [];
                    $rosterPlayerIds = [];

                    foreach ($baseSquad as $playerIndex) {
                        if (isset($usedPlayers[$playerIndex]) || isset($rosterPlayerIds[$playerIndex])) {
                            continue;
                        }
                        $roster[] = ['player' => $playerIndex, 'legionary' => false];
                        $rosterPlayerIds[$playerIndex] = true;
                    }

                    // Fill up to at least 4 players with legionaries
                    $targetSize = max(4, count($roster));
                    $attempts = 0;
                    while (count($roster) < $targetSize && $attempts < 100) {
                        $legIndex = $faker->numberBetween(0, $playerCount - 1);
                        $attempts++;
                        if (!isset($usedPlayers[$legIndex]) && !isset($rosterPlayerIds[$legIndex])) {
                            $roster[] = ['player' => $legIndex, 'legionary' => true];
                            $rosterPlayerIds[$legIndex] = true;
                        }
                    }

                    // 20% chance an extra legionary
                    if ($faker->boolean(20)) {
                        $attempts = 0;
                        do {
                            $legIndex = $faker->numberBetween(0, $playerCount - 1);
                            $attempts++;
                        } while ((isset($usedPlayers[$legIndex]) || isset($rosterPlayerIds[$legIndex])) && $attempts < 50);

                        if (!isset($usedPlayers[$legIndex]) && !isset($rosterPlayerIds[$legIndex])) {
                            $roster[] = ['player' => $legIndex, 'legionary' => true];
                            $rosterPlayerIds[$legIndex] = true;
                        }
                    }

                    // Mark all roster players as used in this tournament
                    foreach ($roster as $entry) {
                        $usedPlayers[$entry['player']] = true;

                        $tstp = new TournamentSessionTeamPlayer();
                        $tstp->setTournamentSessionTeam($st);
                        $tstp->setPlayer($this->getReference("player_{$entry['player']}", Player::class));
                        $tstp->setIsLegionary($entry['legionary']);
                        $manager->persist($tstp);
                    }
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [TeamFixture::class, VenueFixture::class];
    }
}
