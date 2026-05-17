<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\VenueDTO;
use App\Enum\CacheTag;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionRepository;
use App\Repository\VenueRepository;
use App\Repository\VenueRepresentativeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class VenueService
{
    public function __construct(
        private VenueRepository $venueRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private TournamentSessionRepository $sessionRepository,
        private Mapper $mapper,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     */
    public function get(int $id): VenueDTO
    {
        $cacheKey = "venue_show_$id";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            $item->tag([CacheTag::Venues->value]);
            $item->expiresAfter(3600);

            return $this->buildVenueDto($id);
        });
    }

    /**
     * @throws EntityNotFoundException
     */
    private function buildVenueDto(int $id): VenueDTO
    {
        $venue = $this->venueRepository->findWithTown($id)
            ?? throw EntityNotFoundException::forId('Venue', $id);

        /** @var VenueDTO */
        return $this->mapper->map($venue, VenueDTO::class, [
            'representatives' => $this->representativeRepository->findByVenueWithPlayer($venue),
            'tournamentCount' => $this->sessionRepository->countByVenue($venue),
        ]);
    }
}
