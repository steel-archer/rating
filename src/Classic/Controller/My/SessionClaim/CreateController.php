<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim;

use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\DTO\Response\Tournament\VenueOptionDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Enum\TournamentOnlineMode;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepresentativeRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/create/{tournamentId}', name: 'my_session_claim_create', requirements: ['tournamentId' => '\d+'], methods: ['GET'])]
class CreateController extends AbstractController
{
    public function __invoke(
        #[MapEntity(id: 'tournamentId')] Tournament $tournament,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
    ): Response {
        if (!$tournament->isRegistrationOpen()) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        $venues = $mapper->mapMultiple(
            $representativeRepository->findVenuesByPlayer($player),
            VenueOptionDTO::class,
        );

        // Filter out online venues for offline-only tournaments
        if ($tournament->getOnlineMode() === TournamentOnlineMode::Offline) {
            $venues = array_values(array_filter(
                $venues,
                static fn(VenueOptionDTO $venue) => !$venue->isOnline,
            ));
        }

        if ($venues === []) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/session_claim_create.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'venues' => $venues,
        ]);
    }
}
