<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\PlayerDTO;
use App\Enum\CacheTag;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\PlayerRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TournamentSessionTeamPlayerRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private TeamPlayerRepository $teamPlayerRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
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
    public function get(int $id): PlayerDTO
    {
        $cacheKey = "player_show_$id";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->tag([CacheTag::player($id)]);
            $item->expiresAfter(3600);

            return $this->buildPlayerDto($id);
        });
    }

    /**
     * @throws EntityNotFoundException
     */
    private function buildPlayerDto(int $id): PlayerDTO
    {
        $player = $this->playerRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Player', $id);

        /** @var PlayerDTO */
        return $this->mapper->map($player, PlayerDTO::class, [
            'squads' => $this->teamPlayerRepository->findByPlayerWithTeamAndSeason($player),
            'tournamentCount' => $this->sessionTeamPlayerRepository->countByPlayer($player),
        ]);
    }
}
