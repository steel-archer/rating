<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\DTO\Response\Tournament\VenueOptionDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\VenueRepresentativeRepository;
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
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        $venues = $mapper->mapMultiple(
            $representativeRepository->findVenuesByPlayer($player),
            VenueOptionDTO::class,
        );

        if ($venues === []) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/session_claim_create.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'venues' => $venues,
        ]);
    }
}
