<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Common\Contract\PlayerDetailProviderInterface;
use App\Common\DTO\Response\Player\SquadDTO;
use App\Common\DTO\Response\PlayerDTO;
use App\Common\Enum\CacheTag;
use App\Common\Exception\EntityNotFoundException;
use App\Common\Mapping\Mapper;
use App\Common\Repository\PlayerRepository;
use App\Classic\Repository\TeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PlayerService implements PlayerDetailProviderInterface
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

        $teamPlayers = $this->teamPlayerRepository->findByPlayerWithTeamAndSeason($player);
        $squads = array_map(
            fn($tp) => $this->mapper->map($tp, SquadDTO::class),
            $teamPlayers,
        );

        /** @var PlayerDTO */
        return $this->mapper->map($player, PlayerDTO::class, [
            'squads' => $squads,
            'tournamentCount' => $this->sessionTeamPlayerRepository->countByPlayer($player),
        ]);
    }
}
