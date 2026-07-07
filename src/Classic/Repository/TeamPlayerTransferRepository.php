<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\Entity\Team;
use App\Classic\Entity\TeamPlayerTransfer;
use App\Classic\Enum\TeamPlayerTransferType;
use App\Common\Entity\Player;
use App\Common\Entity\Season;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TeamPlayerTransfer> */
class TeamPlayerTransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamPlayerTransfer::class);
    }

    public function countJoinsBySeason(Player $player, Season $season): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.player = :player')
            ->andWhere('t.season = :season')
            ->andWhere('t.type = :type')
            ->setParameter('player', $player)
            ->setParameter('season', $season)
            ->setParameter('type', TeamPlayerTransferType::Joined)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns all player IDs that were in a team's base squad on a given date.
     *
     * @return list<int>
     */
    public function findPlayerIdsInTeamOnDate(
        Team $team,
        Season $season,
        DateTimeImmutable $date,
    ): array {
        $transfers = $this->findAllBySeason($season);

        return $this->resolveTeamPlayerIdsOnDate($transfers, $team, $date);
    }

    /**
     * Batch method: returns squad info for each date from a list of dates.
     * Single DB query for all dates.
     *
     * @param list<DateTimeImmutable> $dates
     * @return array<string, list<int>> date string (Y-m-d) => player IDs in team on that date
     */
    public function findPlayerIdsInTeamOnDates(
        Team $team,
        Season $season,
        array $dates,
    ): array {
        if ($dates === []) {
            return [];
        }

        $transfers = $this->findAllBySeason($season);

        $result = [];
        foreach ($dates as $date) {
            $key = $date->format('Y-m-d');
            $result[$key] = $this->resolveTeamPlayerIdsOnDate($transfers, $team, $date);
        }

        return $result;
    }

    /**
     * Batch method: resolves squad info for multiple teams and dates from pre-loaded transfers.
     *
     * @param list<TeamPlayerTransfer> $transfers pre-loaded transfers for the season
     * @param array<int, array{team: Team, dates: list<DateTimeImmutable>}> $teamDates teamId => team+dates
     * @return array<int, array<string, list<int>>> teamId => (dateKey => playerIds)
     */
    public function resolveSquadFromTransfers(array $transfers, array $teamDates): array
    {
        $result = [];
        foreach ($teamDates as $teamId => $info) {
            $result[$teamId] = [];
            foreach ($info['dates'] as $date) {
                $key = $date->format('Y-m-d');
                $result[$teamId][$key] = $this->resolveTeamPlayerIdsOnDate($transfers, $info['team'], $date);
            }
        }

        return $result;
    }

    /**
     * @return list<TeamPlayerTransfer>
     */
    public function findAllBySeason(Season $season): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.player', 'player')
            ->join('t.team', 'team')
            ->addSelect('player', 'team')
            ->where('t.season = :season')
            ->setParameter('season', $season)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<TeamPlayerTransfer> $transfers
     * @return list<int>
     */
    private function resolveTeamPlayerIdsOnDate(array $transfers, Team $team, DateTimeImmutable $date): array
    {
        $latestByPlayer = [];
        foreach ($transfers as $transfer) {
            if ($transfer->getDate() > $date) {
                continue;
            }
            $playerId = $transfer->getPlayer()->getId();
            if (!isset($latestByPlayer[$playerId])) {
                $latestByPlayer[$playerId] = $transfer;
            }
        }

        $result = [];
        foreach ($latestByPlayer as $playerId => $transfer) {
            if (
                $transfer->getType() === TeamPlayerTransferType::Joined
                && $transfer->getTeam()->getId() === $team->getId()
            ) {
                $result[] = $playerId;
            }
        }

        return $result;
    }
}
