<?php

declare(strict_types=1);

namespace App\Service\Cache;

use App\Entity\Tournament;
use App\Enum\CacheTag;
use App\Repository\TournamentSessionTeamPlayerRepository;
use App\Repository\TournamentSessionTeamRepository;
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
