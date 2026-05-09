<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\TeamDTO;
use App\Enum\CacheTag;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TeamService
{
    public function __construct(
        private TeamRepository $teamRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function get(int $id): TeamDTO
    {
        $cacheKey = "team_show_{$id}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->tag([CacheTag::team($id)]);
            $item->expiresAfter(3600);

            return $this->buildTeamDto($id);
        });
    }

    /**
     * @throws EntityNotFoundException
     */
    private function buildTeamDto(int $id): TeamDTO
    {
        $team = $this->teamRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Team', $id);

        /** @var TeamDTO */
        return $this->mapper->map($team, TeamDTO::class, [
            'teamPlayers' => $this->teamPlayerRepository->findByTeamWithPlayerAndSeason($team),
            'tournamentCount' => $this->sessionTeamRepository->countByTeam($team),
        ]);
    }
}
