<?php

declare(strict_types=1);

namespace App\Common\Service;

use App\Common\Contract\VenueTournamentProviderInterface;
use App\Common\DTO\Response\VenueDTO;
use App\Common\Enum\CacheTag;
use App\Common\Exception\EntityNotFoundException;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepository;
use App\Common\Repository\VenueRepresentativeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class VenueService
{
    public function __construct(
        private VenueRepository $venueRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private VenueTournamentProviderInterface $tournamentProvider,
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
            'tournamentCount' => $this->tournamentProvider->countByVenue($venue),
        ]);
    }
}
