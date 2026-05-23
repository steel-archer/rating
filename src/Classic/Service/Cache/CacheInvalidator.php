<?php

declare(strict_types=1);

namespace App\Classic\Service\Cache;

use App\Classic\Entity\Tournament;
use App\Common\Enum\CacheTag;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheInvalidator
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionTeamPlayerRepository $sessionTeamPlayerRepository,
    ) {
    }

    /**
     * @param list<string> $tags
     *
     * @throws InvalidArgumentException
     */
    public function invalidateTags(array $tags): void
    {
        $this->cache->invalidateTags($tags);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function invalidateTournament(Tournament $tournament): void
    {
        $this->cache->invalidateTags([
            CacheTag::tournament($tournament->getId()),
            CacheTag::TournamentList->value,
        ]);
    }

    /**
     * Invalidates cache for the tournament and all its participating players and teams.
     *
     * @throws InvalidArgumentException
     */
    public function invalidateTournamentWithParticipants(Tournament $tournament): void
    {
        $tags = [
            CacheTag::tournament($tournament->getId()),
            CacheTag::TournamentList->value,
        ];

        $teamIds = $this->sessionTeamRepository->findTeamIdsByTournament($tournament);
        foreach ($teamIds as $teamId) {
            $tags[] = CacheTag::team($teamId);
        }

        $playerIds = $this->sessionTeamPlayerRepository->findPlayerIdsByTournament($tournament);
        foreach ($playerIds as $playerId) {
            $tags[] = CacheTag::player($playerId);
        }

        $this->cache->invalidateTags($tags);
    }
}
