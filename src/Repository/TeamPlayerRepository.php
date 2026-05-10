<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TeamPlayer> */
class TeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamPlayer::class);
    }

    /**
     * @return array<int, array{playerIds: list<int>, captainId: int|null}> teamId => squadInfo
     */
    public function getSquadMapBySeason(Season $season): array
    {
        $rows = $this->createQueryBuilder('tp')
            ->select(
                'IDENTITY(tp.team) AS teamId',
                'IDENTITY(tp.player) AS playerId',
                'tp.isCaptain',
            )
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

    /**
     * @return list<Player>
     */
    public function findPlayersForTeamAndSeason(Team $team, Season $season): array
    {
        return array_map(
            static fn(TeamPlayer $tp) => $tp->getPlayer(),
            $this->createQueryBuilder('tp')
                ->join('tp.player', 'player')
                ->addSelect('player')
                ->where('tp.team = :team')
                ->andWhere('tp.season = :season')
                ->setParameter('team', $team)
                ->setParameter('season', $season)
                ->orderBy('player.lastName')
                ->getQuery()
                ->getResult(),
        );
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findByTeamWithPlayerAndSeason(Team $team): array
    {
        return $this->createQueryBuilder('tp')
            ->join('tp.player', 'player')
            ->leftJoin('player.user', 'playerUser')
            ->join('tp.season', 'season')
            ->addSelect('player', 'playerUser', 'season')
            ->where('tp.team = :team')
            ->setParameter('team', $team)
            ->orderBy('season.startedAt', 'DESC')
            ->addOrderBy('player.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<TeamPlayer>
     */
    public function findByPlayerWithTeamAndSeason(Player $player): array
    {
        return $this->createQueryBuilder('tp')
            ->join('tp.team', 'team')
            ->join('tp.season', 'season')
            ->addSelect('team', 'season')
            ->where('tp.player = :player')
            ->setParameter('player', $player)
            ->orderBy('season.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
