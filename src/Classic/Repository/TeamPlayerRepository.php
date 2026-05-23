<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Common\Entity\Player;
use App\Common\Entity\Season;
use App\Classic\Entity\Team;
use App\Classic\Entity\TeamPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
    public function findByTeamAndSeason(Team $team, Season $season): array
    {
        return $this->createQueryBuilder('tp')
            ->join('tp.player', 'player')
            ->leftJoin('player.user', 'playerUser')
            ->addSelect('player', 'playerUser')
            ->where('tp.team = :team')
            ->andWhere('tp.season = :season')
            ->setParameter('team', $team)
            ->setParameter('season', $season)
            ->orderBy('player.lastName', 'ASC')
            ->getQuery()
            ->getResult();
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
     * @throws NonUniqueResultException
     */
    public function findCaptainEntry(Player $player, Season $season): ?TeamPlayer
    {
        return $this->createQueryBuilder('tp')
            ->join('tp.team', 'team')
            ->join('team.town', 'town')
            ->addSelect('team', 'town')
            ->where('tp.player = :player')
            ->andWhere('tp.season = :season')
            ->andWhere('tp.isCaptain = true')
            ->setParameter('player', $player)
            ->setParameter('season', $season)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
