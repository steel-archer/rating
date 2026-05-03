<?php

namespace App\Repository;

use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamPlayer::class);
    }

    /**
     * @return array{playerIds: list<int>, captainId: int|null}
     */
    public function getSquadInfo(Team $team, Season $season): array
    {
        $rows = $this->createQueryBuilder('tp')
            ->select('IDENTITY(tp.player) AS playerId', 'tp.isCaptain')
            ->where('tp.team = :team')
            ->andWhere('tp.season = :season')
            ->setParameter('team', $team)
            ->setParameter('season', $season)
            ->getQuery()
            ->getArrayResult();

        $playerIds = [];
        $captainId = null;

        foreach ($rows as $row) {
            $playerIds[] = (int) $row['playerId'];
            if ($row['isCaptain']) {
                $captainId = (int) $row['playerId'];
            }
        }

        return ['playerIds' => $playerIds, 'captainId' => $captainId];
    }

    /**
     * @return array<int, array{playerIds: list<int>, captainId: int|null}> teamId => squadInfo
     */
    public function getSquadMapBySeason(Season $season): array
    {
        $rows = $this->createQueryBuilder('tp')
            ->select('IDENTITY(tp.team) AS teamId', 'IDENTITY(tp.player) AS playerId', 'tp.isCaptain')
            ->where('tp.season = :season')
            ->setParameter('season', $season)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $teamId = (int) $row['teamId'];
            $playerId = (int) $row['playerId'];

            if (!isset($result[$teamId])) {
                $result[$teamId] = ['playerIds' => [], 'captainId' => null];
            }

            $result[$teamId]['playerIds'][] = $playerId;
            if ($row['isCaptain']) {
                $result[$teamId]['captainId'] = $playerId;
            }
        }

        return $result;
    }
}
