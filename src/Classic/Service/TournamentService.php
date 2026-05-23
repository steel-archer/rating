<?php

declare(strict_types=1);

namespace App\Classic\Service;

use App\Classic\DTO\Request\TournamentListRequestDTO;
use App\Classic\DTO\Response\Tournament\TournamentListItemDTO;
use App\Classic\DTO\Response\TournamentDTO;
use App\Classic\Entity\Tournament;
use App\Common\Enum\CacheTag;
use App\Common\Exception\EntityNotFoundException;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\AppealRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Repository\TournamentRepository;
use App\Classic\Repository\TournamentSessionRepository;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
use App\Classic\Repository\TournamentSessionTeamRepository;
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
        private TournamentSessionTeamAnswerRepository $answerRepository,
        private AppealRepository $appealRepository,
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
        return $this->cache->get("tournament_show_$id", function (ItemInterface $item) use ($id) {
            $item->tag([CacheTag::tournament($id)]);
            $item->expiresAfter(86400);

            return $this->buildDto($this->getEntity($id));
        });
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getEntity(int $id): Tournament
    {
        return $this->tournamentRepository->findWithSeason($id)
            ?? throw EntityNotFoundException::forId('Tournament', $id);
    }

    /**
     * @return array{tournaments: list<TournamentListItemDTO>, lastPage: int}
     *
     * @throws InvalidArgumentException
     */
    public function getList(TournamentListRequestDTO $requestDto): array
    {
        $cacheKey = 'tournament_list_' . md5(serialize($requestDto));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($requestDto) {
            $item->tag([CacheTag::TournamentList->value]);
            $item->expiresAfter(3600);

            return [
                'tournaments' => $this->tournamentRepository->findForList($requestDto),
                'lastPage' => $this->tournamentRepository->getLastPageNumber($requestDto),
            ];
        });
    }

    private function buildDto(Tournament $tournament): TournamentDTO
    {
        /** @var TournamentDTO */
        return $this->mapper->map($tournament, TournamentDTO::class, [
            'officials' => $this->officialRepository->findByTournament($tournament),
            'teamCount' => $this->sessionTeamRepository->countByTournament($tournament),
            'sessionCount' => $this->sessionRepository->countByTournament($tournament),
            'disputeCount' => $this->answerRepository->countSubmittedDisputesByTournament($tournament),
            'appealCount' => $this->appealRepository->countByTournament($tournament),
        ]);
    }
}
