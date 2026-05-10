<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TournamentSessionTeamPlayer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSessionTeamPlayer> */
class TournamentSessionTeamPlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeamPlayer::class);
    }

    /**
     * @param list<int> $sessionTeamIds
     * @return list<TournamentSessionTeamPlayer>
     */
    public function findBySessionTeamIds(array $sessionTeamIds): array
    {
        if ($sessionTeamIds === []) {
            return [];
        }

        return $this->createQueryBuilder('stp')
            ->join('stp.player', 'p')
            ->leftJoin('p.user', 'pu')
            ->addSelect('p', 'pu')
            ->where('stp.tournamentSessionTeam IN (:ids)')
            ->setParameter('ids', $sessionTeamIds)
            ->orderBy('p.lastName')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countByPlayer(Player $player): int
    {
        return (int) $this->createQueryBuilder('stp')
            ->select('COUNT(stp.id)')
            ->where('stp.player = :player')
            ->setParameter('player', $player)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<int>
     */
    public function findPlayerIdsByTournament(Tournament $tournament): array
    {
        $rows = $this->createQueryBuilder('stp')
            ->select('DISTINCT IDENTITY(stp.player) AS playerId')
            ->join('stp.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 'ts')
            ->where('ts.tournament = :t')
            ->setParameter('t', $tournament)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row) => (int) $row['playerId'], $rows);
    }

    /**
     * @return list<Player>
     */
    public function findPlayersByTeamAndSeason(Team $team, Season $season): array
    {
        $rows = $this->createQueryBuilder('stp')
            ->select('DISTINCT IDENTITY(stp.player) AS playerId')
            ->join('stp.tournamentSessionTeam', 'st')
            ->join('st.tournamentSession', 'ts')
            ->join('ts.tournament', 't')
            ->where('st.team = :team')
            ->andWhere('t.season = :season')
            ->setParameter('team', $team)
            ->setParameter('season', $season)
            ->getQuery()
            ->getArrayResult();

        if ($rows === []) {
            return [];
        }

        $playerIds = array_map(static fn(array $row) => (int) $row['playerId'], $rows);

        $entityManager = $this->getEntityManager();
        $players = $entityManager->getRepository(Player::class)->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $playerIds)
            ->orderBy('p.lastName')
            ->getQuery()
            ->getResult();

        return $players;
    }

    /** @return list<TournamentSessionTeamPlayer> */
    public function findByPlayerPaginated(Player $player, int $page, int $perPage): array
    {
        return $this->createQueryBuilder('stp')
            ->join('stp.tournamentSessionTeam', 'st')
            ->join('st.team', 'team')
            ->join('team.town', 'town')
            ->join('st.tournamentSession', 'ts')
            ->join('ts.tournament', 'tournament')
            ->addSelect('st', 'team', 'town', 'ts', 'tournament')
            ->where('stp.player = :player')
            ->setParameter('player', $player)
            ->orderBy('ts.playedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
}
