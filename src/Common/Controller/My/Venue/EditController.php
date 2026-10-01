<?php

declare(strict_types=1);

namespace App\Common\Controller\My\Venue;

use App\Common\DTO\Response\My\VenueEditDTO;
use App\Common\DTO\Response\My\VenueRepresentativeDTO;
use App\Common\Entity\User;
use App\Common\Entity\Venue;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/venues/{id}/edit', name: 'my_venue_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class EditController extends AbstractController
{
    public function __invoke(
        Venue $venue,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        // Both the creator and any representative may manage the venue.
        $isCreator = $venue->getCreatedBy()?->getId() === $player->getId();
        if (!$isCreator && !$representativeRepository->isRepresentative($player, $venue)) {
            throw $this->createNotFoundException();
        }

        $venueDto = $mapper->map($venue, VenueEditDTO::class);

        // Representatives can only be managed once the venue is approved.
        $representatives = $venue->isApproved()
            ? $mapper->mapMultiple(
                $representativeRepository->findByVenueWithPlayer($venue),
                VenueRepresentativeDTO::class,
            )
            : [];

        return $this->render('my/venue/edit.html.twig', [
            'venue' => $venueDto,
            'representatives' => $representatives,
        ]);
    }
}
