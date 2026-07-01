<?php

declare(strict_types=1);

namespace App\Classic\Repository;

use App\Classic\DTO\Response\Venue\VenueTournamentDTO;
use App\Common\Contract\VenueTournamentProviderInterface;
use App\Common\Entity\Player;
use App\Classic\Entity\SessionClaim;
use App\Classic\Enum\SessionClaimStatus;
use App\Classic\Entity\Tournament;
use App\Classic\Entity\TournamentSession;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Entity\Venue;
use App\Common\Mapping\Mapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSession> */
class TournamentSessionRepository extends ServiceEntityRepository implements VenueTournamentProviderInterface
{
    private const int PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry, private Mapper $mapper)
    {
        parent::__construct($registry, TournamentSession::class);
    }

    /**
     * @return list<TournamentSession>
     */
    public function findByTournamentPaginated(Tournament $tournament, int $page): array
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.venue', 'v')
            ->join('v.town', 'town')
            ->join('ts.representative', 'rep')
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('ts.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->addSelect('v', 'town', 'rep', 'repUser', 'host', 'hostUser')
            ->where('ts.tournament = :t')
            ->andWhere('sc.status = :status')
            ->setParameter('t', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->orderBy('town.name', 'ASC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumberByTournament(Tournament $tournament): int
    {
        return max(1, (int) ceil($this->countByTournament($tournament) / self::PER_PAGE));
    }

    public function countByTournament(Tournament $tournament): int
    {
        return (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->where('ts.tournament = :t')
            ->andWhere('sc.status = :status')
            ->setParameter('t', $tournament)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<VenueTournamentDTO>
     */
    public function findByVenuePaginated(Venue $venue, int $page): array
    {
        $rows = $this->createQueryBuilder('ts')
            ->join('ts.tournament', 't')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->leftJoin(TournamentSessionTeam::class, 'st', 'WITH', 'st.tournamentSession = ts AND st.resultsSubmitted = true')
            ->select(
                't.id AS tournamentId',
                't.name AS tournamentName',
                't.format AS tournamentFormat',
                'ts.playedAt',
                'COUNT(st.id) AS teamsCount',
            )
            ->where('ts.venue = :venue')
            ->andWhere('sc.status = :status')
            ->setParameter('venue', $venue)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->groupBy('ts.id', 't.id', 't.name', 't.format', 'ts.playedAt')
            ->orderBy('ts.playedAt', 'DESC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getArrayResult();

        return $this->mapper->mapMultiple($rows, VenueTournamentDTO::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastPageNumberByVenue(Venue $venue): int
    {
        return max(1, (int) ceil($this->countByVenue($venue) / self::PER_PAGE));
    }

    public function countByVenue(Venue $venue): int
    {
        return (int) $this->createQueryBuilder('ts')
            ->select('COUNT(ts.id)')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->where('ts.venue = :venue')
            ->andWhere('sc.status = :status')
            ->setParameter('venue', $venue)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Counts sessions with submitted results per venue (i.e. actually played games).
     *
     * @param list<int> $venueIds
     * @return array<int, int>
     */
    public function countPlayedByVenueIds(array $venueIds): array
    {
        if ($venueIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('ts')
            ->select('IDENTITY(ts.venue) AS venueId', 'COUNT(DISTINCT ts.id) AS cnt')
            ->join(TournamentSessionTeam::class, 'st', 'WITH', 'st.tournamentSession = ts')
            ->where('ts.venue IN (:venues)')
            ->andWhere('st.resultsSubmitted = true')
            ->setParameter('venues', $venueIds)
            ->groupBy('ts.venue')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['venueId']] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * @see SessionShowController (used via MapEntity expr)
     *
     * @throws NonUniqueResultException
     */
    public function findWithRelations(int $id): ?TournamentSession
    {
        return $this->createQueryBuilder('ts')
            ->join('ts.tournament', 't')
            ->leftJoin('t.season', 's')
            ->join('ts.venue', 'v')
            ->join('v.town', 'town')
            ->join('ts.representative', 'rep')
            ->leftJoin('rep.user', 'repUser')
            ->leftJoin('ts.host', 'host')
            ->leftJoin('host.user', 'hostUser')
            ->join(SessionClaim::class, 'sc', 'WITH', 'sc.session = ts')
            ->addSelect('t', 's', 'v', 'town', 'rep', 'repUser', 'host', 'hostUser')
            ->where('ts.id = :id')
            ->andWhere('sc.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', SessionClaimStatus::Approved->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isRepresentativeOfTournament(Player $player, Tournament $tournament): bool
    {
        return (bool) $this->createQueryBuilder('ts')
            ->select('1')
            ->where('ts.tournament = :tournament')
            ->andWhere('ts.representative = :player')
            ->setParameter('tournament', $tournament)
            ->setParameter('player', $player)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
