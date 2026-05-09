<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\TournamentDTO;
use App\Entity\Tournament;
use App\Enum\CacheTag;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentSessionRepository;
use App\Repository\TournamentSessionTeamRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TournamentService
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentOfficialRepository $officialRepository,
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private TournamentSessionRepository $sessionRepository,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     */
    public function get(int $id): TournamentDTO
    {
        return $this->cache->get("tournament_show_{$id}", function (ItemInterface $item) use ($id) {
            $item->tag([CacheTag::tournament($id)]);
            $item->expiresAfter(86400);

            $tournament = $this->tournamentRepository->findWithSeason($id)
                ?? throw EntityNotFoundException::forId('Tournament', $id);

            return $this->buildDto($tournament);
        });
    }

    private function buildDto(Tournament $tournament): TournamentDTO
    {
        /** @var TournamentDTO */
        return $this->mapper->map($tournament, TournamentDTO::class, [
            'officials' => $this->officialRepository->findByTournament($tournament),
            'teamCount' => $this->sessionTeamRepository->countByTournament($tournament),
            'sessionCount' => $this->sessionRepository->countByTournament($tournament),
        ]);
    }
}
