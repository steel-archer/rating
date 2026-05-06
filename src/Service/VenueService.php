<?php

namespace App\Service;

use App\DTO\Response\VenueDTO;
use App\Exception\EntityNotFoundException;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionRepository;
use App\Repository\VenueRepository;
use App\Repository\VenueRepresentativeRepository;

class VenueService
{
    public function __construct(
        private VenueRepository $venueRepository,
        private VenueRepresentativeRepository $representativeRepository,
        private TournamentSessionRepository $sessionRepository,
        private Mapper $mapper,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): VenueDTO
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
