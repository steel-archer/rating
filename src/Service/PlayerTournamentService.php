<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Player\TournamentAppearanceDTO;
use App\Entity\Player;
use App\Entity\TournamentSessionTeamPlayer;
use App\Enum\CacheTag;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PlayerTournamentService
{
    private const int PER_PAGE = 50;

    public function __construct(
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws DbalException
     * @throws InvalidArgumentException
     * @return list<TournamentAppearanceDTO>
     */
    public function getTournaments(Player $player, int $page): array
    {
        $playerId = $player->getId();
        $cacheKey = "player_tournaments_{$playerId}_page_$page";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($player, $page, $playerId) {
            $item->tag([CacheTag::player($playerId)]);
            $item->expiresAfter(3600);

            return $this->buildTournaments($player, $page);
        });
    }

    /**
     * @throws InvalidArgumentException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getLastPageNumber(Player $player): int
    {
        $playerId = $player->getId();
        $cacheKey = "player_tournaments_last_page_$playerId";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($player, $playerId) {
            $item->tag([CacheTag::player($playerId)]);
            $item->expiresAfter(3600);

            $total = $this->sessionTeamPlayerRepository->countByPlayer($player);

            return max(1, (int) ceil($total / self::PER_PAGE));
        });
    }

    /**
     * @return list<TournamentAppearanceDTO>
     * @throws DbalException
     */
    private function buildTournaments(Player $player, int $page): array
    {
        $appearances = $this->sessionTeamPlayerRepository->findByPlayerPaginated($player, $page, self::PER_PAGE);
        if ($appearances === []) {
            return [];
        }

        $sessionTeamIds = array_map(
            static fn(TournamentSessionTeamPlayer $a) => $a->getTournamentSessionTeam()->getId(),
            $appearances,
        );
        $places = $this->sessionTeamRepository->getPlacesInTournament($sessionTeamIds);

        return array_map(
            fn(TournamentSessionTeamPlayer $a) => $this->mapper->map($a, TournamentAppearanceDTO::class, ['places' => $places]),
            $appearances,
        );
    }
}
